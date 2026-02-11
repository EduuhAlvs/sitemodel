<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Profile;

class HomeController extends Controller
{
    public function index()
    {
        // Captura filtros da URL
        $filters = [
            'search' => $_GET['q'] ?? '',
            'gender' => $_GET['gender'] ?? '',
            'city'   => $_GET['city'] ?? ''
        ];

        // Busca os perfis
        $profiles = Profile::getListPublic($filters, 24);

        // Prepara dados para a View
        $this->view('pages/home', [
            'profiles' => $profiles,
            'filters' => $filters
        ]);
    }
}
