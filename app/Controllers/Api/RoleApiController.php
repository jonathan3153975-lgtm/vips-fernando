<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\NotFoundException;
use App\Core\Request;
use App\Services\RoleService;
use RuntimeException;

final class RoleApiController extends Controller
{
    public function __construct(
        private readonly RoleService $roles,
    ) {
    }

    public function index(): array
    {
        return $this->json(['data' => $this->roles->list()]);
    }

    public function show(int $roleId): array
    {
        return $this->guarded(fn (): array => $this->roles->find($roleId));
    }

    public function permissions(): array
    {
        return $this->json(['data' => $this->roles->availablePermissions()]);
    }

    public function store(): array
    {
        return $this->guarded(fn (): array => $this->roles->create(Request::all()), 201);
    }

    public function update(int $roleId): array
    {
        return $this->guarded(fn (): array => $this->roles->update($roleId, Request::all()));
    }

    public function updatePermissions(int $roleId): array
    {
        return $this->guarded(function () use ($roleId): array {
            $ids = Request::input('permission_ids', []);

            return $this->roles->update($roleId, [
                'permission_ids' => is_array($ids) ? $ids : [],
            ]);
        });
    }

    public function destroy(int $roleId): array
    {
        return $this->guarded(function () use ($roleId): array {
            $this->roles->delete($roleId);

            return ['deleted' => true];
        });
    }

    private function guarded(callable $callback, int $status = 200): array
    {
        try {
            return $this->json(['data' => $callback()], $status);
        } catch (NotFoundException $exception) {
            return $this->json(['message' => $exception->getMessage()], 404);
        } catch (RuntimeException $exception) {
            return $this->json(['message' => $exception->getMessage()], 400);
        }
    }
}
