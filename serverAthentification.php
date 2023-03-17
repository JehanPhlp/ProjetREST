<?php
    /**
     * Login moderator :    moderator_user      moderator_mdp
     * Login publisher :    publisher_user      publisher_mdp
     * Login publisher2 :   publisher2_user     publisher2_mdp
     */

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
            $username = $postedDataTab['username'];
            $mdp = $postedDataTab['mot_de_passe'];  

            $req = $linkpdo->prepare('SELECT role_utilisateur FROM utilisateur WHERE nom = ? and mot_de_passe = ?');
            $req->execute(array($username, hash("sha256",$mdp)));
            $reponseBD = $req->fetchAll(PDO::FETCH_ASSOC);

            //L'utilisateur n'a pas été trouvé dans la base de données
            if(count($reponseBD) == 0) {
                deliver_response(404, "utilisateur introuvable dans la base de données", NULL);
                break;
            }

            $roleUtilisateur = $reponseBD[0];

            $header = array('alg'=>'HS256', 'typ'=>'JWT');
            $payload = array('username'=>$username, 'mot_de_passe'=>$mdp, 'role_utilisateur'=>$roleUtilisateur['role_utilisateur'], 'exp'=>(time()+3600));
            $jwt = generate_jwt($header, $payload);
            deliver_response(200, "Authentification réussi !", $jwt);
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