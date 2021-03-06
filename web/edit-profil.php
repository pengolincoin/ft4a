<?php
include_once 'includes/config.php';

//Si pas connecté pas de connexion à l'espace d'admin --> retour sur la page login
if(!$user->is_logged_in()) {
        header('Location: /admin/login.php');
}

if(isset($_SESSION['username']) && $_SESSION['username'] != $_GET['membre']) {
	header('Location: ../');
}

// Suppression de l'avatar...
if(isset($_GET['delavatar'])) {

	$delavatar = html($_GET['delavatar']);

	// on supprime le fichier image
	$stmt = $db->prepare('SELECT avatar FROM blog_members WHERE memberID = :memberID');
	$stmt->execute(array(
		':memberID' => $delavatar
	));
	$sup = $stmt->fetch();

	$file = $REP_IMAGES_AVATARS.$sup['avatar']; 
	if (file_exists($file)) {
		unlink($file);
	}

	//puis on supprime l'avatar dans la base
	$stmt = $db->prepare('UPDATE blog_members SET avatar = NULL WHERE memberID = :memberID');
	$stmt->execute(array(
                ':memberID' => $delavatar
        ));

	if(isset($_SESSION['username'])) {
		header('Location: /profil.php?action=ok&membre='.html($_SESSION['username']));
	}
}

// titre de la page
if(isset($_SESSION['username'])) {
	$pagetitle = 'Edition du profil de '.html($_SESSION['username']);
}

include_once 'includes/header.php';
?>

<body id="top">

<div class="container">

        <header>

                <!-- titre -->
                <?php include_once 'includes/header-title.php'; ?>

                <!-- navbar -->
                <?php include_once 'includes/navbar.php'; ?>

        </header>

        <div class="container p-3 my-3 border">
                <div class="row">
			<div class="col-sm-9">
				<div class="container bg-light">

				<h4>Edition du profil membre de <?php echo html($_GET['membre']); ?></h4>
				<?php
				$username = html($_GET['membre']);
				//if form has been submitted process it
        			if(isset($_POST['submit'])) {
					extract($_POST);
					if(isset($_FILES['avatar']['name']) && !empty($_FILES['avatar']['name'])) {
						$target_dir = $REP_IMAGES_AVATARS;
                				$target_file = $target_dir . basename($_FILES["avatar"]["name"]);
                				$imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
						if ($_FILES['avatar']['error'] > 0) {
                        				$error[] = 'Erreur lors du transfert de l\'avatar membre.';
                				}
						// On cherche si l'image n'existe pas déjà sous ce même nom
                				if (file_exists($target_file)) {
                        				$error[] = 'Désolé, cet avatar membre existe déjà. Veillez en choisir un autre ou tout simplement changer son nom.';
                				}

                				// Poids de l'image
                				if ($_FILES['avatar']['size'] > $MAX_SIZE_AVATAR) {
                        				$error[] = 'Avatar membre trop gros. Taille maxi : '.makesize($MAX_SIZE_AVATAR);
                				}

                				// format de l'image
                				if($imageFileType != "jpg" && $imageFileType != "png") {
                        				$error[] = 'Désolé : seuls les fichiers jpg et png sont autorisés !';
                				}

                				// Dimensions de l'image
                				$image_sizes = getimagesize($_FILES['avatar']['tmp_name']);
                				if ($image_sizes[0] > $WIDTH_MAX_AVATAR OR $image_sizes[1] > $HEIGHT_MAX_AVATAR) {
                        				$error[] = 'Avatar trop grand : '.$WIDTH_MAX_AVATAR.' x '.$HEIGHT_MAX_AVATAR.' maxi !';
                				}

                				// on vérifie que c'est bien une image
                				if($image_sizes == false) {
                        				$error[] = 'Le fichier envoyé n\'est pas une image !';
                				}

						// on upload l'image s'il n'y a pas d'erreur
                				if(!isset($error)) {
							$avatarmembre = $username . '-avatar-' . $_FILES['avatar']['name'];
                        				//if(!move_uploaded_file($_FILES['avatar']['tmp_name'], $REP_IMAGES_AVATARS.$_FILES['avatar']['name'])) {
							if(!move_uploaded_file($_FILES['avatar']['tmp_name'], $REP_IMAGES_AVATARS.$avatarmembre)) {
                                				$error[] = 'Problème de téléchargement de l\'avatar membre.';
                        				}
                				}
					}//fin de if(isset($_FILES['avatar']['name']))
	
					// On vérifie le mot de passe
					if(!empty($password)) {
						if(strlen($password) < 6) {
                        				$error[] = 'Le mot de passe est trop court ! (6 caractères minimum)';
                				}
                				if($passwordConfirm ==''){
                        				$error[] = 'Veuillez confirmer le mot de passe.';
                				}
                				if($password != $passwordConfirm){
                        				$error[] = 'Les mots de passe ne concordent pas.';
                				}
					}

					// On vérifie l'adresse e-mail
					$email = filter_var($email, FILTER_SANITIZE_EMAIL);
					if(($email =='') || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        			$error[] = 'Veuillez entrer une adresse e-mail valide';
                			}

					if(!isset($error)) {
						try {
                                			if(isset($password) && !empty($password)){
                                        			$hashedpassword = $user->password_hash($password, PASSWORD_BCRYPT);

                                        			//Mise à jour de la base avec le nouveau mot de passe
                                       	 			$stmt = $db->prepare('UPDATE blog_members SET password = :password, email = :email WHERE username = :username') ;
                                        			$stmt->execute(array(
                                                			':username' => $username,
                                                			':password' => $hashedpassword,
                                                			':email' => $email
                                        			));
                                			}

							elseif(isset($_FILES['avatar']['name']) && !empty($_FILES['avatar']['name'])) {
								//Mise à jour de la base avec le nouvel avatar
                                        			$stmt = $db->prepare('UPDATE blog_members SET email = :email, avatar = :avatar WHERE username = :username') ;
                                        			$stmt->execute(array(
                                                			':username' => $username,
                                                			':avatar' => $avatarmembre,
									':email' => $email
                                        			));
							}

							else {
                                        			//Mise à jour de la base avec adresse e-mail seulement. Aucun nouveau mot de passe n'a été soumis ni aucun avatar
                                        			$stmt = $db->prepare('UPDATE blog_members SET email = :email WHERE username = :username') ;
                                        			$stmt->execute(array(
                                                			':username' => $username,
                                                			':email' => $email
                                        			));
                                			}

							write_log('<span class="orange bold">Edition profil utilisateur :</span> '.$username, $db);

							//redirect to page
                                			header('Location: '.SITEURL.'/profil.php?action=ok&membre='.$username);
                                			exit;

							$stmt->closeCursor();
                        			}//Try
						catch(PDOException $e) {
 				                       	echo $e->getMessage();
                        			}
                			}//if !isset $errors
				}//if isset post submit

				//check for any errors
        			if(isset($error)) {
                			foreach($error as $error) {
                        			echo '<div class="alert alert-danger mt-3 alert-dismissible fade show" role="alert">'.$error.'<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
                			}
        			}

				 try {
                        		$stmt = $db->prepare('SELECT memberID,username,email,avatar FROM blog_members WHERE username = :username') ;
                        		$stmt->execute(array(':username' => $username));
                        		$row = $stmt->fetch();
                		}
				catch(PDOException $e) {
                    			echo $e->getMessage();
                		}

        		?>	


			<form class="form-group small" action="" method="post" enctype="multipart/form-data">
				<div class="form-row">
				<div class="col">
					<div class="col-sm-6 form-group">
		 				<label for="username">Pseudo<br>(Ne peut être changé. Sinon, recréez un compte)
                					<input class="form-control" style="width:250px;" type="text" id="username" name="username" value="<?php echo html($row['username']); ?>" readonly>
						</label>
						<br>
                				<label for="password">Mot de passe<br>(Seulement en cas de changement - 6 caractères minimum)
                					<input class="form-control" style="width:250px;" type="password" id="password" name="password" value="">
						</label>
						<br>
                				<label for="passwordConfirm">Confirmez le mot de passe
                					<input class="form-control" style="width:250px;" type="password" id="passwordConfirm" name="passwordConfirm" value="">
						</label>
						<br>
                				<label for="email">E-mail
                					<input class="form-control" style="width:250px;" type="text" id="email" name="email" value="<?php echo html($row['email']);?>">
						</label>
					</div>
					<div class="col-sm-6 form-group">
						<label for="avatar">Avatar (PNG ou JPG | max. <?php echo makesize($MAX_SIZE_AVATAR); ?> | max. <?php echo $WIDTH_MAX_AVATAR; ?> x <?php echo $HEIGHT_MAX_AVATAR; ?> pix.)
                					<input class="form-control" style="width:350px;" type="file" id="avatar" name="avatar">
						</label>
						<br><br>
						Avatar actuel :
						<p>
							<?php
							if(!empty($row['avatar']) && file_exists($REP_IMAGES_AVATARS.$row['avatar'])) {
								echo '<img class="img-thumbnail" style="max-width: 150px;" src="/images/avatars/'.html($row['avatar']).'" alt="Avatar de '.html($row['username']).'" />';
							?>
							<br>
							<a href="javascript:delavatar('<?php echo html($row['memberID']);?>','<?php echo html($row['avatar']);?>')"><i class="fas fa-trash-alt"></i> Supprimer l'avatar</a>
							<?php
							}
							else {
								echo '<img style="max-width:100px;" class="img-thumbnail" src="/images/noimage.png" alt="Pas d\'avatar pour '.html($row['username']).'" />';
							}
							?>
						</p>
					</div>	
					<div class="row">
						<div class="col-md-12 form-group text-center">
							<button class="btn btn-primary mb-2 mt-1" name="submit" type="submit">Mettre à jour</button>
							<button class="btn btn-secondary ml-3 mb-2 mt-1" type="reset">Annuler</button>							
						</div>
					</div>
				</div>
				</div>
        		</form>
		</div> <!-- class container -->


			</div> <!-- //col-sm-9 -->
			
			<!-- sidebar -->
                        <?php include_once 'includes/sidebar.php'; ?>

		</div> <!-- //row -->

		<!-- footer -->
        	<?php include_once 'includes/footer.php'; ?>

	</div> <!-- //container coprs -->

</div> <!-- //container global -->

<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>

</body>
</html>
