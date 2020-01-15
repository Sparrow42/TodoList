<pre>
<?php
//print_r($_POST);
?>
</pre>


<?php
/************************************
今後の実装予定機能
＜優先（高）＞
〇・メモ欄の追加
・日付ごとのTodoリスト管理
 …日付ごとに見れるようにする。
・作業中、作業終了で表を分ける。その中で日にちで分ける。
　→・表の日時を時間表示にする。
・リストの「アーカイブ保存」ボタンを追加（表から非表示。アーカイブフラグ追加。）
・目的、PDCAなどの項目追加（詳細とメモ欄メモで代用はできている）
 …詳細の登録デフォルト設定で対応可。こちらのほうが柔軟にできる。
・継続Todoを設定する（継続フラグ追加。表を別で表示する。）

＜優先（低）＞
・作業実績の表示
・作業実績の分析
・時刻入力方法の改善
・デザインの改善
・メモアーカイブのDB化
未・メモのbr消し
*************************************/


$dsn = 'mysql:dbname=todo_master;host=localhost;';
$user = 'root';
$pass = '';
$pdo = new PDO($dsn, $user, $pass, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));

date_default_timezone_set('Asia/Tokyo');

//編集による更新
if(!empty($_POST['edit_id'])){
    $sql = "UPDATE todo_master SET title=:title, shosai=:shosai ,start=:start ,target=:target ,finish=:finish WHERE id=:edit_id;";
    $stmt = $pdo->prepare($sql);
    $stmt -> bindParam(':title', $_POST['title'], PDO::PARAM_STR);
	$stmt -> bindParam(':shosai', $_POST['shosai'], PDO::PARAM_STR);
	$stmt -> bindParam(':start', $_POST['start'], PDO::PARAM_STR);
    $stmt -> bindParam(':target', $_POST['target'], PDO::PARAM_STR);
    $stmt -> bindParam(':finish', $_POST['finish'], PDO::PARAM_STR);
    $stmt -> bindParam(':edit_id', $_POST['edit_id'], PDO::PARAM_STR);
    $stmt->execute();
}

//行の削除
if(!empty($_POST['remove_id'])){
    $sql = "DELETE FROM todo_master WHERE id=:remove_id;";
    $stmt = $pdo->prepare($sql);
    $stmt -> bindParam(':remove_id', $_POST['remove_id'], PDO::PARAM_INT);
    $stmt->execute();
}

//終了時刻の更新
if(!empty($_POST['finish_id'])){
    $finish_time = date("Y/m/d H:i:s");
    $sql = "UPDATE todo_master SET finish='".$finish_time."' WHERE id=".$_POST['finish_id'];
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
}

//メモ欄の保存・更新

//メモの<br>消しに失敗
// $ontable_memo_nl = file_get_contents("ontable_memo.txt");
// $archive_memo_nl = file_get_contents("archive_memo.txt");
// file_put_contents("archive_memo.txt", nl2br($archive_memo_nl)); //brタグの消去
// file_put_contents("ontable_memo.txt", nl2br($ontable_memo_nl));

if(!empty($_POST['ontable_save'])){
    file_put_contents("ontable_memo.txt", $_POST['memo']);
}
if(!empty($_POST['archive_save'])){
    file_put_contents("archive_memo.txt", nl2br($_POST['memo'])."<br><br>", FILE_APPEND);
    file_put_contents("ontable_memo.txt", "");
}
$ontable_memo = file_get_contents("ontable_memo.txt");
$archive_memo = file_get_contents("archive_memo.txt");
//file_put_contents("archive_memo.txt", br2nl($archive_memo)); //brタグの消去
//file_put_contents("ontable_memo.txt", br2nl($ontable_memo));

if($ontable_memo==''){
    //メモ欄デフォルト設定
    $ontable_memo.=date('Y年m月d日')."\n";
}

$sql = "SELECT * FROM todo_master;";
$stmt = $pdo->query($sql);
while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    $result[]=$row;
}
$non_disp='0000-00-00 00:00:00';

$i=0;
foreach($result as $row){
    if($row['start']==$non_disp) $result[$i]['start']="";
    if($row['target']==$non_disp) $result[$i]['target']="";
    if($row['finish']==$non_disp) $result[$i]['finish']="";
    $i+=1;
}
$keys=array_keys($result[0]);

function br2nl($string){
     // 大文字・小文字を区別しない
    return preg_replace('/<br[[:space:]]*\/?[[:space:]]*>/i', "\n", $string);
}

?>

<html>
    <head>
        <link rel="stylesheet" type="text/css" href="css/table.css">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    </head>
    <h2>Todoリスト　マイメニュー</h2>
    <h3>今日のTodo</h3>
    <form method='post' name='mymenu_form' action='mytodo_menu.php'>
        <a href='mytodo_register.php'>新規登録</a>
        <table border="1">
            <tr>
                <th>行番号</th>
                <th>編集・削除・終了</th>
                <th>タイトル</th>
                <th>詳細</th>
                <th>開始日時</th>
                <th>終了予定日時</th>
                <th>終了日時</th>
            </tr>
            <?php foreach($result as $row){ ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><input type=button id=<?php echo $row['id']; ?> value='編集' onClick="typeChange('<?php echo $row['id']; ?>');">　
                    <input type=button value='削除' onClick="removeConfirm('<?php echo $row['id']; ?>');">
                    <input type=submit value='作業終了' onClick="document.getElementById('finish_id').value='<?php echo $row['id']; ?>';"></td>
                    <?php foreach($keys as $key => $value){ ?>
                        <?php if($key!=0){ ?>
                            <td> <p id="<?php echo 'disp'.$row['id'].$value; ?>"><?php echo $row[$value]; ?></p>
                            <input type=text id="<?php echo 'edit'.$row['id'].$value; ?>" name='<?php echo $value; ?>' value='<?php echo $row[$value]; ?>'></td>
                        <?php } ?>
                    <?php } ?>
                    <?php unset($value); ?>
                </tr>
            <?php } ?>
        </table>
        <input type=hidden id='edit_id' name='edit_id' value=''>
        <input type=hidden id='remove_id' name='remove_id' value=''>
        <input type=hidden id='finish_id' name='finish_id' value=''>
    </form>
    <h3>メモ欄</h3>
    <form method='post' name='memo_form' action='mytodo_menu.php'>
        <textarea name='memo' rows='12' cols='110'><?php echo $ontable_memo; ?></textarea><br>
        <input type="submit" name='ontable_save' value="作業中に一時保存">
        <input type="submit" name='archive_save' value="アーカイブに保存">
        <input type="reset" value="リセット">
    </form>
    <h3>メモアーカイブ</h3>
    <?php echo $archive_memo;?>
</html>

<?php
// foreach($result as $row){
//     echo $row['id'].',';
//     echo $row['title'].',';
//     echo $row['shosai'].',';
//     if($row['start']!=$non_disp) echo $row['start'].',';
//     else echo "指定なし,";
//     if($row['target']!=$non_disp) echo $row['target'].',';
//     else echo "指定なし,";
//     if($row['finish']!=$non_disp) echo $row['finish'];
//     else echo "指定なし";
//     echo '<br />';
// }
?>

<script type="text/javascript">

//初期表示は非表示
<?php foreach($result as $row){ ?>
    <?php foreach($keys as $value){ ?>
        <?php if($value!='id'){ ?>
            document.getElementById('<?php echo 'edit'.$row['id'].$value; ?>').style.display ="none";
            document.getElementById('<?php echo 'edit'.$row['id'].$value; ?>').disabled ='true';
            document.getElementById('<?php echo 'disp'.$row['id'].$value; ?>').style.display ="block";
        <?php } ?>
    <?php } ?>
<?php } ?>

function typeChange(changeId){
    <?php foreach($keys as $key => $value){ ?>
        <?php if($value!='id'){ ?>
            const <?php echo 'disp'.$value; ?> = document.getElementById('disp'+changeId+'<?php echo $value; ?>');
            const <?php echo 'edit'.$value; ?> = document.getElementById('edit'+changeId+'<?php echo $value; ?>');
            if(<?php echo 'disp'.$value; ?>.style.display=="block"){
                <?php echo 'disp'.$value; ?>.style.display ="none";
                <?php echo 'edit'.$value; ?>.style.display ="block";
                <?php echo 'edit'.$value; ?>.disabled ="";
            }else{
                <?php echo 'disp'.$value; ?>.style.display ="block";
                <?php echo 'edit'.$value; ?>.style.display ="none";
            }
        <?php } ?>
    <?php } ?>
    if(document.getElementById(changeId).value=='編集'){
        document.getElementById(changeId).value='保存';
    }else{
        document.getElementById(changeId).value='編集';
        document.getElementById('edit_id').value=changeId;
        document.mymenu_form.submit();
    }
}

function removeConfirm(removeId){
    var conf=window.confirm('行番号'+removeId+'を本当に削除しますか？');
    if(conf==true){
        document.getElementById('remove_id').value=removeId;
        document.mymenu_form.submit();
    }
}
</script>