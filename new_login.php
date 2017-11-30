<?php
	//セッション開始
	session_start();
	$error = array("");
    $secret = "";   //初期化
    if(isset($_POST['secret'])) $secret = $_POST['secret'];
	$you = [0,0,0,0,0];
    $true = 0;
    $s_word = ""; //初期化
    $cnt = 0;
    $i = 0;

	if (isset($_POST['regist'])){
		
    /*選択された場合フラグをオン*/
        if ($secret === 'born')         $you[0] = 1;    
        if ($secret === 'hoby')         $you[1] = 1;
        if ($secret === 'important')    $you[2] = 1;
        if ($secret === 'taste')        $you[3] = 1;
        if ($secret === 'first_love')   $you[4] = 1;

		if (empty($_POST['user_name']))	$error[0] = 'ユーザー名が未入力です。';
		if (empty($_POST['password']))	$error[1] = 'パスワードが未入力です。';

		if (!empty($_POST['user_name']) && !empty($_POST['password'])) {
			$u_name = $_POST['user_name'];
			$u_pass = $_POST['password'];
			$pass_check = $_POST['pass_check'];
			$true++;

			if (preg_match("/^[A-Za-z0-9ぁ-んァ-ヶ－一-龠]{1,16}$/u",$u_name)) {
				$true++;
			}else { $error[2] = "ユーザー名の入力に誤りがあります。"; }

			if (preg_match("/^[a-z0-9]{8,24}$/iu",$u_pass)) {
				if ($u_pass === $pass_check) $true++;
			}else { $error[3] = "パスワードの入力に誤りがあります。"; }
			if ($u_pass === $pass_check) {
				$true++;
				$pass_hash = password_hash($u_pass, PASSWORD_DEFAULT);
				echo $pass_hash;
                if (password_verify($u_pass,$pass_hash)){
                    echo 'Password is valid!';
                } else {
                    echo 'Invalid password.';
                }
			}else { $error[4] = "パスワードが正しくありません。"; }
		}

        if (isset($_POST['secret_word'])) $s_word = $_POST['secret_word'];
            echo nl2br($s_word);
            echo $secret."<br>";
            print_r($you);  //$you の中身確認
            while ($you[$i] == 0) { //選択されていない数
                $i++;
                $cnt++;
                if($cnt >= 5) break;
            }
            if($cnt == 5 && isset($you[$i]) == "") {   //どれも選択されていない場合
                if($s_word != "") $error[5] = "秘密の質問を選択してください。";
            }elseif($s_word == "") $error[6] = "秘密の質問の内容を入力してください。";   
            echo "<br>trueは".$true."<br>";
            echo "cntは".$cnt;

		if (count($error) === 0) {
			 
			header("Content-type: text/html; charset=utf-8");
			 
			//クロスサイトリクエストフォージェリ（CSRF）対策
			$_SESSION['token'] = base64_encode(openssl_random_pseudo_bytes(32));
			$token = $_SESSION['token'];
			 
			//クリックジャッキング対策
			header('X-FRAME-OPTIONS: SAMEORIGIN');
 
			try {

				//接続
				$pdo = new PDO('sqlite:user_data.db');

				// SQL実行時にもエラーの代わりに例外を投げるように設定
					// (毎回if文を書く必要がなくなる)
					$pdo -> setAttribute ( PDO :: ATTR_ERRMODE , PDO ::ERRMODE_EXCEPTION );

					// デフォルトのフェッチモードを連想配列形式に設定
				// (毎回PDO::FETCH_ASSOCを指定する必要が無くなる)
					$pdo -> setAttribute ( PDO :: ATTR_DEFAULT_FETCH_MODE , PDO :: FETCH_ASSOC );

					//テーブル作成
					$pdo -> exec ( "CREATE TABLE IF NOT EXISTS user(
			  id INTEGER PRIMARY KEY AUTOINCREMENT,
			  name VARCHAR(16),
			  pass VARCHAR(24),
			  you VARCHAR(10),
			  secret VARCHAR(100)
			  )" );

					//挿入（プリペアドステートメント）
				$stmt = $pdo -> prepare("INSERT INTO user(id, name, pass, you, secret)
				 VALUES (?,:u_name,:u_pass,:you,:s_word)"); //SQL文
				$stmt -> bindValue(':u_name', $u_name, PDO::PARAM_STR);
				$stmt -> bindValue(':u_pass', $pass_hash, PDO::PARAM_STR);
				$stmt -> bindValue(':you', $secret, PDO::PARAM_STR);
				$stmt -> bindValue(':s_word', $s_word ,PDO::PARAM_STR);
				$stmt->execute();
				/*$pdo->query($stmt); //DB($pdo)にSQL文($stmt)を投げる*/
				var_dump($stmt);
				unset($pdo);
				//($db,$u_name);
				$_SESSION['user'] = $u_name;
				echo '<script>
				alert("登録が完了しました。");
				location.href = "schedule.php";
				</script>';

			} catch (Exception $e)	{
				echo $e -> getMessage () . PHP_EOL ;
			}		
		}

	}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>新規アカウント作成</title>
	</head>
	<body>
		<h1>新規アカウント作成</h1>
		<form action = "" method = "post">
		<p style="color:red;">
			<?php
				foreach ($error as $value) {
					echo $value . "<br>";
				}
			 ?>
		</p><br>
			ユーザー名：<input type = "text" name = "user_name">
			<font style="color:red;">※入力必須<br></font>
			パスワード：<input type = "password" name = "password">
			<font style="color:red;">※入力必須<br></font>
			パスワードの確認:<input type = "password" name = "pass_check">
			<font style="color:red;">※入力必須<br></font>
			秘密の質問(/ω＼):<select name = "secret" id = "secret">
                <option value = "" <?= $secret === '' ? 'selected':'';?>></option>
                <option value = "born" <?= $secret === 'born' ? 'selected':'';?>>あなたの生まれた場所は？</option>
                <option value = "hoby" <?= $secret === 'hoby' ? ' selected' : ''; ?>>あなたの趣味は？</option>
                <option value = "important" <?= $secret === 'important' ? ' selected' : ''; ?>>あなたの一番大切なものは？</option>
                <option value = "taste" <?= $secret === 'taste' ? ' selected' : ''; ?>>あなたの好きな味覚は？</option>
                <option value = "first_love" <?= $secret === 'first_love' ? ' selected' : ''; ?>>あなたの初恋の人の名は？</option>
            </select><br>
			<textarea name="secret_word" rows="4" cols="40" maxlength="100" placeholder="内容を記述してください"></textarea><br>
			<input type = "checkbox" name = "hold">パスワードを記憶する<br>
			<!--クッキーを使う-->
			<?php
				if (isset($_COOKIE['hold'])) $hold = $_COOKIE['hold'];
				$expire = time() + 30 * 24 * 3600;	//一か月後（有効期限）
				setcookie('hold','password',$expire);	//クッキーの送信
			?>
			<input type = "submit" value = "新規登録" name = "regist">
			<input type = "reset" value = "キャンセル"><br>
			<a href = "help.php">ヘルプ</a><br>
			<script type="text/javascript">
				var element = document.getElementById("select");
				for (var i=0;i<element.options.length;i++) {
					var option = element.options[i];
					document.write('「'+option.value+"」は"+option.selected+"<br>");
				}
			</script>
		 </form>		
	</body>
</html>