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