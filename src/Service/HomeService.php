<?php

namespace App\Service;

use App\Repository\HomeRepository;

class HomeService
{
    public function __construct(
        public HomeRepository $homeRepository,
    )
    {
    }

}