<?php
// Database configuration
class DatabaseConfig {
    private $host = 'localhost';
    private $db_name = 'boganto_blog';
    private $username = 'root';
    private $password = 'Vj2004@jangid';
    public $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                                $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}

// CORS headers for React frontend
$allowed_origins = [
    'http://localhost:5173', 
    'http://localhost:3000',
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// For e2b.dev sandbox, allow the frontend URL
if (strpos($origin, 'e2b.dev') !== false && strpos($origin, '5173-') !== false) {
    header("Access-Control-Allow-Origin: $origin");
} elseif (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header("Access-Control-Allow-Origin: http://localhost:5173");
}
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Helper function to send JSON response
function sendResponse($data, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($data);
    exit();
}

// Helper function to sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Helper function to generate slug
function generateSlug($string) {
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
}  

// Helper function to upload file
function uploadFile($file, $subfolder = '') {
    $upload_dir = '../uploads/';
    
    // Create subfolder if specified
    if ($subfolder) {
        $upload_dir .= $subfolder . '/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
    }
    
    // Check for upload errors
    if (!isset($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
        error_log('Upload error: ' . $file['error']);
        return false;
    }
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        error_log('Invalid file type: ' . $file['type']);
        return false;
    }
    
    // Validate file size (5MB max)
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $max_size) {
        error_log('File too large: ' . $file['size']);
        return false;
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $file_path = $upload_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        return '/uploads/' . ($subfolder ? $subfolder . '/' : '') . $filename;
    }
    
    error_log('Failed to move uploaded file to: ' . $file_path);
    return false;
}

// Helper function to convert relative image paths to full URLs
function getFullImageUrl($imagePath) {
    if (!$imagePath) {
        return null;
    }
    
    // If it's already a full URL, return as-is
    if (strpos($imagePath, 'http') === 0) {
        return $imagePath;
    }
    
    // Determine the appropriate base URL
    $baseUrl = 'http://localhost:8000';
    if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'e2b.dev') !== false) {
        // In e2b.dev sandbox, always use HTTPS for external URLs
        $baseUrl = 'https://' . $_SERVER['HTTP_HOST'];
    }
    
    // If it's a relative path to uploads, convert to full URL
    if (strpos($imagePath, '/uploads/') === 0) {
        return $baseUrl . $imagePath;
    }
    
    // If it's just a filename, assume it's in uploads
    if (strpos($imagePath, '/') === false) {
        return $baseUrl . '/uploads/' . $imagePath;
    }
    
    return $imagePath;
}
?>