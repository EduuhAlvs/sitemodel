<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PDO;
use Exception;

class LocationsController extends Controller
{
    public function getCities()
    {
        // Limpa qualquer saída anterior (espaços em branco, erros) para garantir JSON puro
        if (ob_get_level()) {
            ob_clean();
        }
        header('Content-Type: application/json');

        $countryId = intval($_GET['country_id'] ?? 0);

        if ($countryId <= 0) {
            echo json_encode([]);
            return;
        }

        $db = Database::getInstance();
        try {
            // Busca id e nome das cidades
            $sql = "SELECT id, name FROM cities WHERE country_id = ? ORDER BY name ASC";

            $stmt = $db->getConnection()->prepare($sql);
            $stmt->execute([$countryId]);
            $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($cities);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro SQL: ' . $e->getMessage()]);
        }
        exit;
    }

    // Busca para autocomplete (caso precise no futuro)
    public function search()
    {
        if (ob_get_level()) {
            ob_clean();
        }
        header('Content-Type: application/json');

        $q = $_GET['q'] ?? '';
        if (strlen($q) < 3) {
            echo json_encode([]);
            return;
        }

        $db = Database::getInstance();
        try {
            $stmt = $db->getConnection()->prepare("SELECT id, name FROM cities WHERE name LIKE ? LIMIT 20");
            $stmt->execute(["%$q%"]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            echo json_encode([]);
        }
        exit;
    }
}
