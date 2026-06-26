// backend/apps/health/handlers/index.php
<?php

namespace apps\health\handlers;

class index {
    public function get($app) {
        $app->setLayout(false);
        header('Content-Type: application/json');
        
        $status = [
            'status' => 'healthy',
            'timestamp' => date('c'),
            'version' => '1.0.0',
            'php_version' => phpversion(),
            'database' => $this->checkDatabase()
        ];
        
        echo json_encode($status);
    }
    
    private function checkDatabase() {
        try {
            $db = \DB::getConnection();
            $db->query('SELECT 1');
            return 'connected';
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }
}