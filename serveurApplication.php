<?php
    require_once("login.php");
    require_once("jwt_utils.php");

    try {
        $linkpdo = new PDO("mysql:host=$server;dbname=$db", $login, $mdp);
    }
    catch (Exception $e) {
       die('Erreur : ' . $e->getMessage());
    }

    /// Paramétrage de l'entête HTTP (pour la réponse au Client)
    header("Content-Type:application/json");

    $jwt_token = get_bearer_token();

    /// Identification du type de méthode HTTP envoyée par le client
    $http_method = $_SERVER['REQUEST_METHOD'];

    switch ($http_method){

        case "GET" :
            $posts = [];
            if (!empty($_GET['id'])){
                $posts = getPosts($_GET['id']);
            }else{
                $posts = getPosts();
            }
            deliver_response(200,"affichage de posts",$post)
            break;
        case "POST" :
            $postedData = file_get_contents('php://input');

            break;
        case "PUT" :
            $postedData = file_get_contents('php://input');

            break;
        case "DELETE":
            if(!is_jwt_valid($jwt_token)) {
                deliver_response(401, "token invalide", NULL);
                break;
            }
            $tokenParts = explode('.', $jwt_token);
            $payload = base64_decode($tokenParts[1]);
            $roleUtilisateur = json_decode($payload)->role_utilisateur;

            /// Récupération de l'identifiant de la ressource envoyé par le Client
            if (!empty($_GET['id'])){
            /// Traitement
                $req = $linkpdo->prepare('DELETE from chuckn_facts where id = ?');
                $req->execute(array($_GET['id']));
            }
            /// Envoi de la réponse au Client
            deliver_response(200, "Votre message", NULL);
            break;
        default :
            deliver_response(405, "Methode non implemenee", NULL);
            break;
        }
    
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

        function getPosts(){
            try {
                $select = $linkpdo->prepare('SELECT * FROM post');
                $select->execute();
                $posts = $select->fetchAll(PDO::FETCH_ASSOC);
                return $posts;
            } catch(Exception $e) {
                echo"erreur";
                die('Erreur:'.$e->getMessage());
            }
        }
        function getPosts($id){
            try {
                $select = $linkpdo->prepare('SELECT * FROM post WHERE id = ?');
                $select->execute(array($id));
                $post = $select->fetchAll(PDO::FETCH_ASSOC);
                return $post;
            } catch(Exception $e) {
                echo"erreur";
                die('Erreur:'.$e->getMessage());
            }
        }
?>