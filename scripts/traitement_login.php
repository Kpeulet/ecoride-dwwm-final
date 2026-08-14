<?php
// 1. Démarrer la session
session_start();

// 2. Connexion à la base de données
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire de login
    $email = htmlspecialchars(trim($_POST['email']));
    $password_brut = $_POST['password'];

    if (empty($email) || empty($password_brut)) {
        header('Location: ../login.php?error=Veuillez+remplir+tous+les+champs.');
        exit;
    }

    try {
        // Recherche de l'utilisateur par email
        $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Vérification du mot de passe haché
        if ($user && password_verify($password_brut, $user['password'])) {
            
            // ==================== [US 13] SÉCURITÉ : VÉRIFICATION DE LA SUSPENSION ====================
            // Si le statut du compte existe et qu'il vaut 'suspendu', on refuse la connexion
            if (isset($user['statut_compte']) && $user['statut_compte'] === 'suspendu') {
                header('Location: ../login.php?error=Votre+compte+a+été+suspendu+par+l\'administrateur.');
                exit;
            }
            // ==========================================================================================
            
            // Stockage des informations en Session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['pseudo']  = $user['pseudo'];
            $_SESSION['role']    = $user['role']; // 'admin', 'employe' ou 'utilisateur'

            // AIGUILLAGE SELON LE RÔLE
            var_dump($_SESSION['role']);
            switch ($_SESSION['role']) {
                case 'admin':
                    header('Location: ../espace_admin.php');
                    break;
                
                case 'employe':
                    header('Location: ../espace_employe.php');
                    break;
                    
                case 'utilisateur':
                    // ==================== [CORRECTIF UX] REDIRECTION INTELLIGENTE SIMPLIFIÉE ====================
                    if (isset($_SESSION['redirect_url'])) {
                        // basename permet de nettoyer l'URL pour ne garder que "details_trajet.php?id=XX"
                        $destination = basename($_SESSION['redirect_url']);
                        unset($_SESSION['redirect_url']); // On nettoie la session
                        
                        // On remonte d'un dossier (car on est dans /scripts/) pour aller à la racine
                        header("Location: ../" . $destination);
                    } else {
                        // Comportement standard si connexion classique
                        header('Location: ../mon_espace.php?success=Ravi+de+vous+revoir+!');
                    }
                    // ============================================================================================
                    break;
                    
                default:
                    header('Location: ../index.php');
                    break;
            }
            exit;

        } else {
            header('Location: ../login.php?error=Identifiants+incorrects.');
            exit;
        }

    } catch (PDOException $e) {
        error_log("Erreur connexion : " . $e->getMessage());
        header('Location: ../login.php?error=Une+erreur+technique+est+survenue.');
        exit;
    }
} else {
    header('Location: ../login.php');
    exit;
}