<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Profile;
use App\Models\Photo;
use App\Models\Location;
use App\Models\Language;


class PublicProfileController extends Controller {

    public function show($slug) {
        // Busca dados principais
        $profile = Profile::getBySlug($slug);

        if (!$profile) {
            http_response_code(404);
            echo "Perfil não encontrado."; // Idealmente renderizar uma view de 404 bonita
            return;
        }

        // Busca dados relacionados
        $photos = Photo::getAllByProfile($profile['id']);
        $locations = Location::getByProfile($profile['id']);
        $languages = Language::getByProfile($profile['id']);
        
        // Decodifica JSONs
        $profile['service_details'] = json_decode($profile['service_details'] ?? '{}', true);
        $profile['working_hours'] = json_decode($profile['working_hours'] ?? '[]', true);

        // Renderiza a view
        $this->view('pages/profile_detail', [
            'title' => $profile['display_name'] . ' - TOP Model',
            'profile' => $profile,
            'photos' => $photos,
            'locations' => $locations,
            'languages' => $languages
        ]);
    }
}