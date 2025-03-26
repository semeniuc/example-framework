<?php

declare(strict_types=1);

namespace App\Controller;

use App\Kernel\Controller\Controller;
use App\Service\HomeService;

class HomeController extends Controller
{
    public function index(): void
    {
        $clasName = HomeService::class;

        /** @var HomeService $homeService */
        $homeService = $this->get($clasName);
        $this->view('home');
    }
}