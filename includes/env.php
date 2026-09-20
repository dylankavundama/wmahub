<?php
/**
 * WMA HUB - Environment Variable Loader (.env)
 * Permet de charger de manière sécurisée les variables d'environnement depuis le fichier .env
 */

if (!function_exists('loadEnv')) {
    /**
     * Charge les variables définies dans un fichier .env
     *
     * @param string $filePath Chemin vers le fichier .env
     * @return bool
     */
    function loadEnv(string $filePath): bool {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return false;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return false;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Ignorer les commentaires et lignes vides
            if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, ';')) {
                continue;
            }

            // Découpage clé = valeur
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));

            if ($key === '') {
                continue;
            }

            // Gestion des guillemets (simples ou doubles)
            $len = strlen($value);
            if ($len >= 2) {
                $firstChar = $value[0];
                $lastChar = $value[$len - 1];
                if (($firstChar === '"' && $lastChar === '"') || ($firstChar === "'" && $lastChar === "'")) {
                    $value = substr($value, 1, -1);
                } else {
                    // Supprimer les commentaires inline si pas entre guillemets
                    $commentPos = strpos($value, ' #');
                    if ($commentPos !== false) {
                        $value = trim(substr($value, 0, $commentPos));
                    }
                }
            } else {
                $commentPos = strpos($value, ' #');
                if ($commentPos !== false) {
                    $value = trim(substr($value, 0, $commentPos));
                }
            }

            // Conversion des valeurs booléennes et nulles sous forme de texte
            $lowerVal = strtolower($value);
            if ($lowerVal === 'true' || $lowerVal === '(true)') {
                $parsedVal = 'true';
            } elseif ($lowerVal === 'false' || $lowerVal === '(false)') {
                $parsedVal = 'false';
            } elseif ($lowerVal === 'null' || $lowerVal === '(null)') {
                $parsedVal = '';
            } else {
                $parsedVal = $value;
            }

            // Affectation dans les superglobales et l'environnement
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $parsedVal;
            }
            if (!array_key_exists($key, $_SERVER)) {
                $_SERVER[$key] = $parsedVal;
            }
            putenv("{$key}={$parsedVal}");
        }

        return true;
    }
}

if (!function_exists('env')) {
    /**
     * Récupère la valeur d'une variable d'environnement avec valeur de secours
     *
     * @param string $key Clé de la variable
     * @param mixed $default Valeur par défaut
     * @return mixed
     */
    function env(string $key, mixed $default = null): mixed {
        $val = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($val === false || $val === null || $val === '') {
            return $default;
        }

        $lower = is_string($val) ? strtolower($val) : '';
        if ($lower === 'true' || $lower === '(true)') {
            return true;
        }
        if ($lower === 'false' || $lower === '(false)') {
            return false;
        }
        if ($lower === 'null' || $lower === '(null)' || $lower === 'empty' || $lower === '(empty)') {
            return $default;
        }

        return $val;
    }
}

// Auto-chargement du .env situé à la racine du projet
loadEnv(dirname(__DIR__) . '/.env');
