<?php
// Configuration
define('GITHUB_SECRET', 'my_secret_key'); // The same secret you set in GitHub
define('REPO_PATH', '/path/to/your/repo'); // Path to your repository on the server
define('BRANCH', 'refs/heads/main'); // The branch to deploy (e.g., main or master)

// Read and verify the payload
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$payload = file_get_contents('php://input');
if ($signature !== 'sha256=' . hash_hmac('sha256', $payload, GITHUB_SECRET)) {
    http_response_code(403);
    exit('Invalid signature');
}

// Parse the JSON payload
$data = json_decode($payload, true);
if (!$data || $data['ref'] !== BRANCH) {
    exit('Not the target branch or invalid payload');
}

// Pull the latest changes
try {
    if (!file_exists(REPO_PATH)) {
        throw new Exception('Repository path does not exist');
    }

    // Change to the repository directory
    chdir(REPO_PATH);

    // Pull the latest changes
    $output = [];
    exec('git reset --hard', $output); // Discard any local changes
    exec('git pull origin ' . basename(BRANCH), $output);

    // Log the deployment process
    file_put_contents('deploy.log', implode("\n", $output) . "\n", FILE_APPEND);
    echo "Deployment successful!";
} catch (Exception $e) {
    // Log any errors
    file_put_contents('deploy.log', $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo "Deployment failed: " . $e->getMessage();
}
?>
