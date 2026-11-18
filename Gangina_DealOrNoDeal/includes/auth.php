<?php
// includes/auth.php - registered users are kept in data/users.json (no database).

function users_read(): array {
    if (!is_file(USERS_FILE)) {
        return [];
    }
    $data = json_decode((string)file_get_contents(USERS_FILE), true);
    return is_array($data) ? $data : [];
}

// Validates and saves a new account. Returns an array of field errors
// (empty array = success). The file is locked so two sign-ups cannot collide.
function user_register(string $name, string $pass, string $confirm): array {
    $errors = [];
    if (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $name)) {
        $errors['username'] = 'Use 3 to 20 letters, digits or underscores.';
    }
    if (strlen($pass) < 8) {
        $errors['password'] = 'Use at least 8 characters.';
    }
    if ($pass !== $confirm) {
        $errors['confirm'] = 'The passwords do not match.';
    }
    if ($errors) {
        return $errors;
    }

    $fh = @fopen(USERS_FILE, 'c+');
    if (!$fh) {
        return ['form' => 'The user file cannot be written. Check the permissions of the data folder.'];
    }
    flock($fh, LOCK_EX);
    $users = json_decode((string)stream_get_contents($fh), true);
    if (!is_array($users)) {
        $users = [];
    }
    $key = strtolower($name);
    if (isset($users[$key])) {
        $errors['username'] = 'That username is already taken.';
    } else {
        $users[$key] = [
            'name'    => $name,
            'hash'    => password_hash($pass, PASSWORD_DEFAULT),
            'created' => time(),
        ];
        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, json_encode($users, JSON_PRETTY_PRINT));
        fflush($fh);
    }
    flock($fh, LOCK_UN);
    fclose($fh);
    return $errors;
}

// Returns the stored display name when the credentials are correct, else null.
function user_verify(string $name, string $pass): ?string {
    $users = users_read();
    $user  = $users[strtolower($name)] ?? null;
    if ($user && password_verify($pass, $user['hash'])) {
        return $user['name'];
    }
    return null;
}
