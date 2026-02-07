<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Profile;
use App\Models\City; 

class HomeController extends Controller {

    public function index() {
        // Captura filtros da URL
        $filters = [
            'city'   => $_GET['city'] ?? '',
            'gender' => $_GET['gender'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];

        // Busca perfis filtrados
        $profiles = Profile::getListPublic(20, $filters);
        
        // Busca cidades existentes para o dropdown
        $cities = City::getAll();

        $this->view('pages/home', [
            'profiles' => $profiles,
            'cities'   => $cities,
            'filters'  => $filters
        ]);
    }
}