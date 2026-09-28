<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\NotFoundException;
use App\Core\Request;
use App\Services\ExchangeRateService;
use RuntimeException;

final class ExchangeRateApiController extends Controller
{
    public function __construct(
        private readonly ExchangeRateService $rates,
    ) {
    }

    public function index(): array
    {
        return $this->json(['data' => $this->rates->list()]);
    }

    public function store(): array
    {
        try {
            return $this->json(['data' => $this->rates->store(Request::all())], 201);
        } catch (NotFoundException $exception) {
            return $this->json(['message' => $exception->getMessage()], 404);
        } catch (RuntimeException $exception) {
            return $this->json(['message' => $exception->getMessage()], 400);
        }
    }
}
