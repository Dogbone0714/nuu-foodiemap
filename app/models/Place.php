<?php
// Place.php
namespace App\Models;

use App\Config\Database;
use PDO;

class Place {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function getAllPlaces() {
        $stmt = $this->pdo->query("SELECT * FROM places ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPlaceById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM places WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createPlace($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO places (name, description, lat, lng, category, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['lat'],
            $data['lng'],
            $data['category']
        ]);
    }

    public function updatePlace($id, $data) {
        $stmt = $this->pdo->prepare("
            UPDATE places 
            SET name = ?, 
                description = ?, 
                lat = ?, 
                lng = ?, 
                category = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['lat'],
            $data['lng'],
            $data['category'],
            $id
        ]);
    }

    public function deletePlace($id) {
        $stmt = $this->pdo->prepare("DELETE FROM places WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
