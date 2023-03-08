<?php
    require_once("login.php");
    require("jwt_utils.php");

    try {
        $linkpdo = new PDO("mysql:host=$server;dbname=$db", $login, $mdp);
    }
    catch (Exception $e) {
       die('Erreur : ' . $e->getMessage());
    }

    /// Paramétrage de l'entête HTTP (pour la réponse au Client)
    header("Content-Type:application/json");

    /// Identification du type de méthode HTTP envoyée par le client
    $http_method = $_SERVER['REQUEST_METHOD'];

    switch($http_method) {
        case "POST":
            /// Récupération des données envoyées par le Client
            $postedData = file_get_contents('php://input');
            $postedDataTab = json_decode($postedData, true);
            $username = htmlentities($postedDataTab['username']);
            $mdp = htmlentities($postedDataTab['mot_de_passe']);

            $req = $linkpdo->prepare('SELECT role_utilisateur FROM Utilisateur WHERE Nom = ? and mot_de_passe = ?');
            $req->execute(array($username, $mdp));
            $reponseBD = $req->fetchAll();

            //L'utilisateur n'a pas été trouvé dans la base de données
            if(sizeof($reponseBD) == 0) {
                deliver_response(404, "utilisateur introuvable dans la base de données", NULL);
            }

            $roleUtilisateur = $reponseBD['role_utilisateur'][0];

            

            $header = array('alg'=>'HS256', 'typ'=>'JWT');
            $payload = array('username'=>$username, 'mot_de_passe'=>$mdp, 'role_utilisateur'=>$roleUtilisateur)
            generate_jwt();
            break;
        default :
            deliver_response(405, "méthode non supporté par le serveur", NULL);
            break;
    }

    /// Envoi de la réponse au Client
    function deliver_response($status, $status_message, $data){
        /// Paramétrage de l'entête HTTP, suite
        header("HTTP/1.1 $status $status_message");
        /// Paramétrage de la réponse retournée
        $response['status'] = $status;
        $response['status_message'] = $status_message;
        $response['data'] = $data;
        /// Mapping de la réponse au format JSON
        $json_response = json_encode($response);
        echo $json_response;
    }

?>