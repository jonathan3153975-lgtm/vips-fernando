<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\NotFoundException;
use App\Core\Request;
use App\Services\UserService;
use RuntimeException;

final class UserApiController extends Controller
{
    public function __construct(
        private readonly UserService $users,
    ) {
    }

    public function index(): array
    {
        return $this->json(['data' => $this->users->list()]);
    }

    public function show(int $userId): array
    {
        return $this->guarded(fn (): array => $this->users->find($userId));
    }

    public function store(): array
    {
        return $this->guarded(fn (): array => $this->users->create(Request::all()), 201);
    }

    public function update(int $userId): array
    {
        return $this->guarded(fn (): array => $this->users->update($userId, Request::all()));
    }

    public function storePassword(int $userId): array
    {
        return $this->guarded(function () use ($userId): array {
            $password = (string) (Request::input('password') ?? '');

            return $this->users->changePassword($userId, $password);
        });
    }

    public function block(int $userId): array
    {
        return $this->guarded(fn (): array => $this->users->block($userId));
    }

    public function activate(int $userId): array
    {
        return $this->guarded(fn (): array => $this->users->activate($userId));
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
