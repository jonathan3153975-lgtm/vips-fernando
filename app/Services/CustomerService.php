<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AuditLogRepository;
use App\Repositories\CustomerRepository;
use RuntimeException;

/**
 * Regras do cadastro de clientes (EPIC 07).
 *
 * O repositorio so faz consulta e escrita; a politica de negocio fica aqui, e
 * toda escrita passa por `audit()` — um cadastro de cliente e dado pessoal e
 * precisa deixar rastro de quem mudou o que.
 */
final class CustomerService
{
    public function __construct(
        private readonly CustomerRepository $customers,
        private readonly AuditLogRepository $auditLogs,
    ) {
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array{data: list<array<string, mixed>>, total: int}
     */
    public function paginate(array $filters, int $limit, int $offset): array
    {
        $filters = $this->normalizeFilters($filters);

        return [
            'data' => $this->customers->paginate($filters, $limit, $offset),
            'total' => $this->customers->countFiltered($filters),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function find(int $customerId): array
    {
        return $this->customers->findOrFail($customerId);
    }

    /**
     * Cadastro. Valida antes de gravar, e o documento duplicado vira 400 com
     * mensagem de dominio — nao um "duplicate key" do driver.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $data = $this->validate($data);

        if ($data['document'] !== null && $this->customers->documentExists($data['document'])) {
            throw new RuntimeException('Ja existe um cliente com este documento neste tenant.');
        }

        $customer = $this->customers->create($data);

        $this->audit('customer.create', $customer['id'], ['name' => $customer['name'], 'document' => $customer['document']]);

        return $customer;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function update(int $customerId, array $data): array
    {
        // findOrFail primeiro: nao ha como editar cliente de outro tenant, e a
        // checagem de documento duplicado precisa do registro existente para
        // excluir o proprio id da comparacao.
        $this->customers->findOrFail($customerId);

        $data = $this->validate($data);

        if ($data['document'] !== null && $this->customers->documentExists($data['document'], $customerId)) {
            throw new RuntimeException('Ja existe outro cliente com este documento neste tenant.');
        }

        $customer = $this->customers->update($customerId, $data);

        $this->audit('customer.update', $customer['id'], ['name' => $customer['name'], 'status' => $customer['status']]);

        return $customer;
    }

    /**
     * Remove ou bloqueia, conforme o historico.
     *
     * Regra 1 do modulo: cliente com venda nunca e apagado, porque a venda
     * perderia o vinculo com quem comprou. Sem historico, apagar e seguro e
     * evita cadastro fantasma.
     *
     * Nao e excecao: um DELETE pode ter dois desfechos legitimos e legiveis
     * (200 apagado, 200 bloqueado), e a API precisa contar a diferenca. Lancar
     * excecao aqui obrigaria o controller a adivinhar de onde veio o erro.
     *
     * @return array{customer: array<string, mixed>, blocked: bool, purchases: int}
     */
    public function delete(int $customerId): array
    {
        $customer = $this->customers->findOrFail($customerId);
        $purchases = $this->customers->countCompletedSales($customerId);

        if ($purchases > 0) {
            $this->customers->deactivate($customerId);

            $this->audit('customer.block', $customerId, ['purchases' => $purchases]);

            return ['customer' => $customer, 'blocked' => true, 'purchases' => $purchases];
        }

        $this->customers->delete($customerId);

        $this->audit('customer.delete', $customerId, ['name' => $customer['name']]);

        return ['customer' => $customer, 'blocked' => false, 'purchases' => 0];
    }

    /**
     * Resumo comercial: total gasto, compras, ultima compra, ticket medio e
     * lucro. Vai junto do detalhe para a tela do cliente nao fazer duas
     * consultas com o mesmo criterio.
     *
     * @return array<string, mixed>
     */
    public function show(int $customerId): array
    {
        $customer = $this->customers->findOrFail($customerId);

        return ['customer' => $customer, 'summary' => $this->customers->summary($customerId)];
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array{data: list<array<string, mixed>>, total: int}
     */
    public function history(int $customerId, array $filters, int $limit, int $offset): array
    {
        $this->customers->findOrFail($customerId);

        $status = strtoupper(trim((string) ($filters['status'] ?? '')));
        $filters = $status === '' ? [] : ['status' => $status];

        return [
            'data' => $this->customers->purchases($customerId, $filters, $limit, $offset),
            'total' => $this->customers->countPurchases($customerId, $filters),
        ];
    }

    /**
     * Validacao e normalizacao do payload.
     *
     * O documento vira so digito: "123.456.789-01" e "12345678901" sao o mesmo
     * CPF, e se guardassemos os dois, o `UNIQUE (tenant_id, document)` nao
     * pegaria a duplicata (as strings diferem) enquanto a busca por documento
     * mostraria duas linhas para a mesma pessoa. Guardar canonico faz o indice
     * do banco ser a fonte da verdade.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function validate(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));

        if ($name === '') {
            throw new RuntimeException('Informe o nome do cliente.');
        }

        if (mb_strlen($name) > 150) {
            throw new RuntimeException('O nome do cliente deve ter no maximo 150 caracteres.');
        }

        $email = $this->optionalText($data['email'] ?? null, 150);

        if ($email !== null && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('Informe um e-mail valido.');
        }

        $status = strtoupper(trim((string) ($data['status'] ?? CustomerRepository::STATUS_ACTIVE)));

        if ($status === '') {
            $status = CustomerRepository::STATUS_ACTIVE;
        }

        if (!in_array($status, CustomerRepository::STATUSES, true)) {
            throw new RuntimeException('Situacao invalida para cliente.');
        }

        return [
            'name' => $name,
            'document' => $this->normalizeDocument($data['document'] ?? null),
            'phone' => $this->optionalText($data['phone'] ?? null, 30),
            'whatsapp' => $this->optionalText($data['whatsapp'] ?? null, 30),
            'email' => $email,
            'address' => $this->optionalText($data['address'] ?? null),
            'notes' => $this->optionalText($data['notes'] ?? null),
            'status' => $status,
        ];
    }

    /**
     * So digito, ou NULL. Vazio e NULL (e nao string vazia) porque o indice
     * unico trata NULL como distinta: dois clientes sem documento convivem, duas
     * strings vazias colidiria.
     */
    private function normalizeDocument(mixed $document): ?string
    {
        if (!is_string($document) && !is_int($document)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $document) ?? '';

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) > 30) {
            throw new RuntimeException('O documento deve ter no maximo 30 caracteres.');
        }

        return $digits;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeFilters(array $filters): array
    {
        $normalized = [];

        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $normalized['search'] = $search;
        }

        $status = strtoupper(trim((string) ($filters['status'] ?? '')));

        if ($status !== '') {
            if (!in_array($status, CustomerRepository::STATUSES, true)) {
                throw new RuntimeException('Filtro de situacao invalido para cliente.');
            }

            $normalized['status'] = $status;
        }

        $hasSales = (string) ($filters['has_sales'] ?? '');

        if ($hasSales === '1' || $hasSales === '0') {
            $normalized['has_sales'] = $hasSales;
        }

        return $normalized;
    }

    private function optionalText(mixed $value, ?int $maxLength = null): ?string
    {
        if ($value === null || (!is_string($value) && !is_int($value))) {
            return null;
        }

        $text = trim((string) $value);

        if ($text === '') {
            return null;
        }

        if ($maxLength !== null && mb_strlen($text) > $maxLength) {
            throw new RuntimeException('Campo com conteudo longo demais.');
        }

        return $text;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function audit(string $action, int $customerId, array $context): void
    {
        // Sem try/catch: enfeitar erro de auditoria com um catch que so relanca
        // nao mudaria nada. `create()` ja devolve void e grava dentro do tenant
        // corrente.
        $this->auditLogs->create($action, 'customer', $customerId, $context);
    }
}