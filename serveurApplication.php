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
                if (is_jwt_valid($jwt_token) && $_GET['username']==get_username($jwt_token)){
                    $posts = getPostFromUser($_GET['username'],$jwt_token);
                }
                $posts = getPostFromUser($_GET['username'],$jwt_token);
            } else if (!empty($_GET['id'])){
                $posts = getPost($_GET['id'],$jwt_token);
            } else {
                $posts = getPosts($jwt_token);
            }
            deliver_response(200,"affichage de posts",$posts);
            break;
        case "POST" :
            //Creer un post
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
            if(!is_jwt_valid($jwt_token)) {
                deliver_response(401, "token invalide", NULL);
                break;
            }

            // Verificationde l'id du post
            if (empty($postedDataTab['id_post'])){
                deliver_response(422, "missing parameter : id_post", NULL);
                break;
            }
            if (!idPostExist($postedDataTab['id_post'])) {
                deliver_response(404, "Ce post n'existe pas", NULL);
                break;
            }
            
            //procedure pour liker ou disliker un post
            if(!empty($postedDataTab["like"]) && ($postedDataTab["like"] != 1 || $postedDataTab[""] != -1)) {
                deliver_response(422, "wrong parameter : like = (1:like / -1:dislike)");
                break;
            } else if (!empty($postedDataTab["like"])) {
                if(!is_publisher($jwt_token)) {
                    deliver_response(401, "Vous n'etes pas autorisé à liker ce post", NULL);
                    break;
                }
                if(is_publisher_of_this_post($jwt_token, $postedDataTab['id_post'])) {
                    deliver_response(401, "vous ne pouvez pas liker votre propre post");
                    break;
                }
                if($postedDataTab["like"] == 1) {
                    likerUnPost($postedDataTab["id_post"], $jwt_token);
                    deliver_response(200, "post liké !", NULL);
                } else {
                    dislikerUnPost($postedDataTab["id_post"], $jwt_token);
                    deliver_response(200, "post disliké !", NULL);
                }
                break;
            }

            //modifier le contenu d'un post
            if(!empty($postedDataTab["contenu"])) {
                if(!is_publisher_of_this_post($jwt_token, $postedDataTab['id_post']) || !is_moderateur($jwt_token)) {
                    deliver_response(401, "Vous n'etes pas autorisé à modifier ce post");
                    break;
                }

            }

            break;
        case "DELETE":
            //Supprimer un post
            if(!is_jwt_valid($jwt_token)) {
                deliver_response(401, "token invalide", NULL);
                break;
            }
            if (empty($_GET['id_post'])){
                deliver_response(422, "missing parameter : id_post", NULL);
                break;
            }
            if (!idPostExist($postedDataTab['id_post'])) {
                deliver_response(404, "Ce post n'existe pas", NULL);
                break;
            }
            if(!is_moderateur($jwt_token) && !is_publisher_of_this_post($jwt_token, $_GET['id_post'])) {
                deliver_response(401, "vous n'etes pas autorisé à supprimer ce post",null);
                break;
            }            

            //suppression du post
            $req = createDB()->prepare('DELETE from post where id_Post = ?');
            $req->execute(array($_GET['id_post']));
        
            deliver_response(200, "Post correctement supprimé", NULL);
            break;
        default :
            deliver_response(405, "Methode non implemenee", NULL);
            break;
    }

    function is_moderateur($jwt_token) {
        return get_role_utilisateur($jwt_token) == 'moderator';
    }

    function is_publisher($jwt_token) {
        return get_role_utilisateur($jwt_token) == "publisher";
    }

    function likerUnPost($id_post, $jwt_token) {
        $id_utilisateur = getIdUserFromUsername(get_username($jwt_token));
        if(estDejaLikeOuDislike($id_utilisateur, $id_post)) {
            deliver_response(405, "Vous avez déja liker ou diliker ce post", NULL);
        } else {
            try {
                $req = createDB()->prepare('INSERT INTO liker (Id_Utilisateur, Id_Post) VALUES (?, ?)');
                $req->execute(array($id_utilisateur, $id_post));
            }
            catch (Exception $e) {
                die('Erreur : ' . $e->getMessage());
            }
        }
    }

    function idPostExist($id_post) {
        $req = createDB()->prepare('SELECT * FROM post WHERE Id_Post = ?');
        $req->execute(array($id_post));
        $reponseBD = $req->fetchAll(PDO::FETCH_ASSOC);

        return (count($reponseBD) != 0);
    }

    function estDejaLikeOuDislike($id_utilisateur, $id_post) {
        try {
            $req = createDB()->prepare('SELECT * FROM liker WHERE Id_Utilisateur = ? AND Id_Post = ?');
            $req->execute(array($id_utilisateur, $id_post));
            $reponseBDLike = $req->fetchAll(PDO::FETCH_ASSOC);

            $req = createDB()->prepare('SELECT * FROM disliker WHERE Id_Utilisateur = ? AND Id_Post = ?');
            $req->execute(array($id_utilisateur, $id_post));
            $reponseBDDislike = $req->fetchAll(PDO::FETCH_ASSOC);

            return(count($reponseBDDislike) != 0 || count($reponseBDLike) != 0);
        } 
        catch (Exception $e) {
            die('Erreur : ' . $e->getMessage());
        }
    }

    function dislikerUnPost($id_post, $jwt_token) {
        $id_utilisateur = getIdUserFromUsername(get_username($jwt_token));
        if(estDejaLikeOuDislike($id_utilisateur, $id_post)) {
            deliver_response(405, "Vous avez déja liker ou diliker ce post", NULL);
        } else {
            try {
                $req = createDB()->prepare('INSERT INTO disliker (Id_Utilisateur, Id_Post) VALUES (?, ?)');
                $req->execute(array($id_utilisateur, $id_post));
            }
            catch (Exception $e) {
                die('Erreur : ' . $e->getMessage());
            }
        }
    }

    function is_publisher_of_this_post($jwt_token, $id_post) {
        if(!is_publisher($jwt_token)) {
            return false;
        }
        $tokenParts = explode('.', $jwt_token);
        $payload = base64_decode($tokenParts[1]);
        $username = json_decode($payload)->username;
        
        try {
            $req = createDB()->prepare('SELECT p.id_utilisateur FROM utilisateur u, post p WHERE u.id_utilisateur = p.id_utilisateur AND u.nom = ? AND p.Id_Post = ?');
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
        $decodedPayload = json_decode($payload, true);
        $roleUtilisateur = $decodedPayload['role_utilisateur'];
        deliver_response(200,$roleUtilisateur,$roleUtilisateur);

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

    function getPosts($jwt_token){
        try {
            if(is_moderateur($jwt_token)){
            $select = createDB()->prepare('SELECT p.Id_Post, p.Id_utilisateur, p.contenu, p.date_publication,
                                            COUNT(l.Id_Post) AS likes, GROUP_CONCAT(DISTINCT ul.nom) AS users_likes,
                                            COUNT(d.Id_Post) AS dislikes, GROUP_CONCAT(DISTINCT ud.nom) AS users_dislikes
                                            FROM post p
                                            LEFT JOIN liker l ON p.Id_Post = l.Id_Post
                                            LEFT JOIN utilisateur ul ON l.Id_utilisateur = ul.Id_utilisateur
                                            LEFT JOIN disliker d ON p.Id_Post = d.Id_Post
                                            LEFT JOIN utilisateur ud ON d.Id_utilisateur = ud.Id_utilisateur
                                            GROUP BY p.Id_Post, p.Id_utilisateur, p.contenu, p.date_publication;');
            }
            else if(is_publisher($jwt_token)){
                $select = createDB()->prepare('SELECT p.Id_Post, p.Id_utilisateur, p.contenu, p.date,
                                                COUNT(l.Id_Post) AS likes,
                                                COUNT(d.Id_Post) AS dislikes
                                                FROM post p
                                                LEFT JOIN liker l ON p.Id_Post = l.Id_Post
                                                LEFT JOIN disliker d ON p.Id_Post = d.Id_Post
                                                GROUP BY p.Id_Post, p.Id_utilisateur, p.contenu, p.date;');
            }
            else{
                $select = createDB()->prepare('SELECT * from post');
            }
            $select->execute();
            $posts = $select->fetchAll(PDO::FETCH_ASSOC);
            return $posts;
        } catch(Exception $e) {
            echo"erreur";
            die('Erreur:'.$e->getMessage());
        }
    }

    function getPost($id,$jwt_token){
        try {
            if(is_moderateur($jwt_token)){
                $select = createDB()->prepare('SELECT p.Id_Post, p.Id_utilisateur, p.contenu, p.date_publication,
                                                COUNT(l.Id_Post) AS likes, GROUP_CONCAT(DISTINCT ul.nom) AS users_likes,
                                                COUNT(d.Id_Post) AS dislikes, GROUP_CONCAT(DISTINCT ud.nom) AS users_dislikes
                                                FROM post p
                                                LEFT JOIN liker l ON p.Id_Post = l.Id_Post
                                                LEFT JOIN utilisateur ul ON l.Id_utilisateur = ul.Id_utilisateur
                                                LEFT JOIN disliker d ON p.Id_Post = d.Id_Post
                                                LEFT JOIN utilisateur ud ON d.Id_utilisateur = ud.Id_utilisateur
                                                WHERE p.Id_Post = ?
                                                GROUP BY p.Id_Post, p.Id_utilisateur, p.contenu, p.date_publication;');
                }
                else if(is_publisher($jwt_token)){
                    $select = createDB()->prepare('SELECT p.Id_Post, p.Id_utilisateur, p.contenu, p.date,
                                                    COUNT(l.Id_Post) AS likes,
                                                    COUNT(d.Id_Post) AS dislikes
                                                    FROM post p
                                                    LEFT JOIN liker l ON p.Id_Post = l.Id_Post
                                                    LEFT JOIN disliker d ON p.Id_Post = d.Id_Post
                                                    WHERE p.Id_Post = ?
                                                    GROUP BY p.Id_Post, p.Id_utilisateur, p.contenu, p.date;');
                }
                else{
                    $select = createDB()->prepare('SELECT * FROM post WHERE post.Id_Post = ?');
                }
            $select->execute(array($id));
            $post = $select->fetchAll(PDO::FETCH_ASSOC);
            return $post;
        } catch(Exception $e) {
            echo"erreur";
            die('Erreur:'.$e->getMessage());
        }
    }

    function getPostFromUser($username,$jwt_token){
        try{
            if(is_moderateur($jwt_token)){
                $select = createDB()->prepare('SELECT p.Id_post, p.Id_utilisateur, p.contenu, p.date_publication,
                                                COUNT(l.Id_Post) AS likes, GROUP_CONCAT(DISTINCT ul.nom) AS users_likes,
                                                COUNT(d.Id_Post) AS dislikes, GROUP_CONCAT(DISTINCT ud.nom) AS users_dislikes
                                                FROM post p
                                                LEFT JOIN liker l ON p.Id_post = l.Id_Post
                                                LEFT JOIN utilisateur ul ON l.Id_utilisateur = ul.Id_utilisateur
                                                LEFT JOIN disliker d ON p.Id_post = d.Id_Post
                                                LEFT JOIN utilisateur ud ON d.Id_utilisateur = ud.Id_utilisateur
                                                JOIN utilisateur u ON p.Id_utilisateur = u.Id_utilisateur AND u.nom = ?
                                                GROUP BY p.Id_post, p.Id_utilisateur, p.contenu, p.date_publication;');
                }
                else if(is_publisher($jwt_token)){
                    $select = createDB()->prepare('SELECT p.Id_Post, p.Id_utilisateur, p.contenu, p.date,
                                                    COUNT(l.Id_Post) AS likes,
                                                    COUNT(d.Id_Post) AS dislikes
                                                    FROM post p,utilisateur
                                                    LEFT JOIN liker l ON p.Id_Post = l.Id_Post
                                                    LEFT JOIN disliker d ON p.Id_Post = d.Id_Post
                                                    JOIN utilisateur u ON p.Id_utilisateur = u.Id_utilisateur AND u.nom = ?
                                                    GROUP BY p.Id_Post, p.Id_utilisateur, p.contenu, p.date;');
                }
                else{
                    $select = createDB()->prepare('SELECT Id_Post,post.Id_Utilisateur,contenu,date_publication FROM post,utilisateur as u WHERE post.Id_Utilisateur=u.Id_Utilisateur and u.nom=?');
                }
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