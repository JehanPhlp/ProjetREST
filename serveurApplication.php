<?php
    require_once("jwt_utils.php");
    require_once("login.php");


    /// Paramétrage de l'entête HTTP (pour la réponse au Client)
    header("Content-Type:application/json");

    $jwt_token = get_bearer_token();

    /// Identification du type de méthode HTTP envoyée par le client
    $http_method = $_SERVER['REQUEST_METHOD'];

    switch ($http_method){

        case "GET" :
            $posts = [];
            if (!empty($_GET['username'])){
                $posts = getPostFromUser($_GET['username']);
            } else if (!empty($_GET['id'])){
                $posts = getPost($_GET['id']);
            } else {
                $posts = getPosts();
            }
            deliver_response(200,"affichage de posts",json_encode($post));
            break;
        case "POST" :
            $postedData = file_get_contents('php://input');
            if(!is_jwt_valid($jwt_token)) {
                deliver_response(401, "Identification requise", NULL);
                break;
            }
            creatPost(get_username($jwt_token),json_decode($postedData, true)['contenu']);
            deliver_response(201, "post cree", NULL);
            break;
        case "PUT" :
            $postedData = file_get_contents('php://input');
            $postedDataTab = json_decode($postedData, true);
            if (empty($_GET['id_post'])){
                deliver_response(422, "missing parameter : id_post", NULL);
            }
            if(!is_jwt_valid($jwt_token)) {
                deliver_response(401, "token invalide", NULL);
                break;
            }
            if(!is_moderateur($jwt_token) && !is_publisher_of_this_post($jwt_token, $_GET['id_post'])) {
                deliver_response(401, "vous n'etes pas autorisé à supprimer ce post");
            }
            if(isVide($postedDataTab['contenu'])) {
                deliver_response(384, "contenu article vide", NULL);
                break;
            }

            break;
        case "DELETE":
            if (empty($_GET['id_post'])){
                deliver_response(422, "missing parameter : id_post", NULL);
            }
            if(!is_jwt_valid($jwt_token)) {
                deliver_response(401, "token invalide", NULL);
                break;
            }
            if(!is_moderateur($jwt_token) && !is_publisher_of_this_post($jwt_token, $_GET['id_post'])) {
                deliver_response(401, "vous n'etes pas autorisé à supprimer ce post");
            }            

            $req = createDB()->prepare('DELETE from post where id_Post = ?');
            $req->execute(array($_GET['id_post']));
        
            /// Envoi de la réponse au Client
            deliver_response(200, "Post correctement supprimé", NULL);
            break;
        default :
            deliver_response(405, "Methode non implemenee", NULL);
            break;
    }

    function is_moderateur($jwt_token) {
        return get_role_utilisateur() == 'moderator';
    }

    function is_publisher($jwt_token) {
        return get_role_utilisateur() == "publisher";
    }

    function is_publisher_of_this_post($jwt_token, $id_post) {
        if(!is_publisher) {
            return false;
        }
        $tokenParts = explode('.', $jwt_token);
        $payload = base64_decode($tokenParts[1]);
        $username = json_decode($payload)->username;
        
        try {
            $req = createDB()->prepare('SELECT p.id_utilisateur FROM utilisateur u, post p WHERE u.id_utilisateur = p.id_utilisateur AND u.nom = ? AND p.id_post = ?');
            $req->execute(array($username, $id_post));
            $reponseBD = $req->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            die('Erreur : ' . $e->getMessage());
        }
        

        return(count($reponseBD) > 0);
    }

    function get_role_utilisateur($jwt_token) {
        $tokenParts = explode('.', $jwt_token);
        $payload = base64_decode($tokenParts[1]);
        $roleUtilisateur = json_decode($payload)->role_utilisateur;
        return $roleUtilisateur;
    }

    function get_username($jwt_token) {
        $tokenParts = explode('.', $jwt_token);
        $payload = base64_decode($tokenParts[1]);
        $nomUtilisateur = json_decode($payload)->username;
        return $nomUtilisateur;
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
            $select = createDB()->prepare('SELECT * FROM post');
            $select->execute();
            $posts = $select->fetchAll(PDO::FETCH_ASSOC);
            return $posts;
        } catch(Exception $e) {
            echo"erreur";
            die('Erreur:'.$e->getMessage());
        }
    }

    function getPost($id){
        try {
            $select = createDB()->prepare('SELECT * FROM post WHERE id = ?');
            $select->execute(array($id));
            $post = $select->fetchAll(PDO::FETCH_ASSOC);
            return $post;
        } catch(Exception $e) {
            echo"erreur";
            die('Erreur:'.$e->getMessage());
        }
    }

    function getPostFromUser($username){
        try{
            $select = createDB()->prepare('SELECT * FROM post,utilisateur as u WHERE post.Id_Utilisateur=u.Id_Utilisateur and u.nom=?');
            $select->execute(array($username));
            $posts = $select->fetchAll(PDO::FETCH_ASSOC);
            return $posts;
        } catch(Exception $e) {
            echo"erreur";
            die('Erreur:'.$e->getMessage());
        }
    }

    function creatPost($username,$contenu){
        try {
            $req = createDB()->prepare('INSERT INTO post(contenu,Id_Utilisateur) values(?,?)');
            $req->execute(array($contenu,getIdUserFromUsername($username)));
        } catch(Exception $e) {
            echo"erreur";
            die('Erreur:'.$e->getMessage());
        }
    }
    function getIdUserFromUsername($username){
        try{
            $select = createDB()->prepare('SELECT Id_Utilisateur FROM utilisateur as u WHERE u.nom=?');
            $select->execute(array($username));
            $id = $select->fetchColumn();
            return intval($id['Id_Utilisateur']);
        } catch(Exception $e) {
            echo"erreur";
            die('Erreur:'.$e->getMessage());
        }
    }

    function createDB(){
        global $server;
        global $db;
        global $login;
        global $mdp;
        try {
            $linkpdo = new PDO("mysql:host=$server;dbname=$db;charset=UTF8", $login, $mdp);
            $linkpdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        catch (Exception $e) {
            die('Erreur : ' . $e->getMessage());
        }
        return $linkpdo;
    }
?>