<?php

declare(strict_types=1);

namespace App\Controller;

use App\Kernel\Controller\Controller;
use App\Kernel\DI\ServiceLocator;
use App\Service\HomeService;

class HomeController extends Controller
{
    public function index(): void
    {
        /** @var HomeService $homeService */
        $homeService = ServiceLocator::getInstance()->get(HomeService::class);
        $this->view('home');
    }
}