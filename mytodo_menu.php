<pre>
<?php
//print_r($_POST);
?>
</pre>


<?php
/*****************************************************
今後の実装予定機能
＜優先（高）＞
〇・メモ欄の追加
・日付ごとのTodoリスト管理
 …日付ごとに見れるようにする。
〇・作業中、作業終了で表を分ける。
　→・その中で日にちで分ける。
　　→・表の日時を時間表示にする。
〇・id仕様からクラス仕様に整理する（大きく改変必要。やるなら早めにやっておきたい。）
　…〇・イメージは、行ごとにdivで異なるid付与、クラスはdispとeditの２つのみ付与。
　　divのid指定からのclass指定により、指定行のみの操作が可能になるはず。
　　…trへのid指定により完了。divは効かない。
〇・作業開始ボタンの追加。
〇・入力欄大きく。新規登録と編集の詳細欄。
・時間指定しやすくする。
　…input=timeで対応する、
〇・時間指定しない入力パターンも必要。
〇・重要度、推定所要時間のカラムを作る。
　→・regとmenuにも入力・編集・表示を対応させる。
　　　…regは完了。menuは所要時間が完了、重要度が未完。
・表に日にちいらない。
・メモ欄の一時保存を遷移時に必ずするように。
〇・作業開始ボタンつける。
・表のずれを直す。
　…幅の問題でcssを変えても変化しない。日時などを省略してから。
・進行度ステータス（progress_flag）を作る。
・menuで作業順の入力できるようにする。
・並び順を重要度・作業順に変更する。

＜優先（中）＞
・継続Todoを設定する（継続フラグ追加。表を別で表示する。）
・リストの「アーカイブ保存」ボタンを追加（表から非表示。アーカイブフラグ追加。）
〇・表のタイトルから終了日時のループをhtmlに直す。(regとmenuどちらも)
・登録日を作る。作業開始日とは別。
〇・作業開始を押すと、終了予定日時が自動入力される。
　…ついでにオブジェクト指向にした。
・目的、PDCAなどの項目追加（詳細とメモ欄で代用はできている）
 …詳細の登録デフォルト設定で対応可。こちらのほうが柔軟にできる。

＜優先（低）＞
・作業中断に対応させる。
・作業実績の表示
・作業実績の分析
・時刻入力方法の改善
・デザインの改善
・メモアーカイブのDB化
未・メモのbr消し
*******************************************************/

$dsn = 'mysql:dbname=todo_master;host=localhost;';
$user = 'root';
$pass = '';
$pdo = new PDO($dsn, $user, $pass, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));

date_default_timezone_set('Asia/Tokyo');
$date = new DateTime('now');

//編集による更新
if(!empty($_POST['edit_id'])){
    $needtime_est = $_POST['needhour_est']*60 + $_POST['needmin_est'];
    $sql = "UPDATE todo_master 
    SET title=:title, shosai=:shosai, start=:start, target=:target, 
    finish=:finish, needtime_est=:needtime_est, needhour_est=:needhour_est, 
    needmin_est=:needmin_est 
    WHERE id=:edit_id;";
    $stmt = $pdo->prepare($sql);
    $stmt -> bindParam(':title', $_POST['title'], PDO::PARAM_STR);
	$stmt -> bindParam(':shosai', $_POST['shosai'], PDO::PARAM_STR);
	$stmt -> bindParam(':start', $_POST['start'], PDO::PARAM_STR);
    $stmt -> bindParam(':target', $_POST['target'], PDO::PARAM_STR);
    $stmt -> bindParam(':finish', $_POST['finish'], PDO::PARAM_STR);
    $stmt -> bindParam(':needtime_est', $needtime_est, PDO::PARAM_INT);
    $stmt -> bindParam(':needhour_est', $_POST['needhour_est'], PDO::PARAM_INT);
    $stmt -> bindParam(':needmin_est', $_POST['needmin_est'], PDO::PARAM_INT);
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
    $finish_time = $date->format('Y/m/d H:i:s');
    $sql = "UPDATE todo_master SET finish='".$finish_time."' WHERE id=".$_POST['finish_id'];
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
}

//開始時刻・終了予定日時の更新
if(!empty($_POST['start_id'])){
    $start_time = $date->format('Y/m/d H:i:s');

    //終了予定日時の演算
    $sql = "SELECT needtime_est FROM todo_master WHERE id=".$_POST['start_id'];
    $stmt = $pdo->query($sql);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);   
    $interval = 'PT'.$row['needtime_est'].'M';
    $date->add(new DateInterval($interval));
    $target_time = $date->format('Y/m/d H:i:s');
    
    $sql = "UPDATE todo_master 
    SET start='".$start_time."', target='".$target_time."' 
    WHERE id=".$_POST['start_id'];
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
}


//メモの<br>消しに失敗
// $ontable_memo_nl = file_get_contents("ontable_memo.txt");
// $archive_memo_nl = file_get_contents("archive_memo.txt");
// file_put_contents("archive_memo.txt", nl2br($archive_memo_nl));
// file_put_contents("ontable_memo.txt", nl2br($ontable_memo_nl));

//メモ欄の保存・更新
if(!empty($_POST['ontable_save'])){
    file_put_contents("ontable_memo.txt", $_POST['memo']);
}
if(!empty($_POST['archive_save'])){
    file_put_contents("archive_memo.txt", nl2br($_POST['memo'])."<br><br>", FILE_APPEND);
    file_put_contents("ontable_memo.txt", "");
}
$ontable_memo = file_get_contents("ontable_memo.txt");
$archive_memo = file_get_contents("archive_memo.txt");
//file_put_contents("archive_memo.txt", br2nl($archive_memo));
//file_put_contents("ontable_memo.txt", br2nl($ontable_memo));

if($ontable_memo==''){
    /***** メモ欄デフォルト設定 *****/
    $date = new DateTime('now');
    //$ontable_memo.=date('Y年m月d日')."\n";
    $ontable_memo.= $date->format('Y年m月d日');
}

$sql = "SELECT * FROM todo_master;";
$stmt = $pdo->query($sql);
$non_input='0000-00-00 00:00:00';
$ongoing_result=[];
$finished_result=[];
$result=[];
while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    //推定所要時間の桁・単位の調整
    // if($row['needhour_est']==0){
    //     $row['needhour_est']='';
    // }else{
    //     $row['needhour_est'].='時間';
    //     if($row['needmin_est']==0){
    //         $row['needmin_est']='00';
    //     }
    // }
    // $row['needmin_est'].='分';

    //指定なし時刻非表示用
    if($row['start']==$non_input) $row['start']="";
    if($row['target']==$non_input) $row['target']="";
    if($row['finish']==$non_input){
        $row['finish']="";
        $ongoing_result[]=$row;
    }else{
        $finished_result[]=$row;
    }
    $result[]=$row;
}

function br2nl($string){
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
        <a href='mytodo_register.php'>新規登録</a><br>
        <br>達成済み項目<br>
        <table border="1">
            <tr>
                <th>行番号</th>
                <th class="button"></th>
                <th>タイトル</th>
                <th>詳細</th>
                <th class="needtime_est">推定所要時間</th>
                <th>開始日時</th>
                <th>終了予定日時</th>
                <th>終了日時</th>
            </tr>
            <?php foreach($finished_result as $row){ ?>
                <tr id='<?php echo "rowid".$row['id']; ?>'>
                    <td><?php echo $row['id']; ?></td>
                    <td>
                        <input type=button id=<?php echo $row['id']; ?> value='編集' onClick="typeChange('<?php echo $row['id']; ?>');">　
                        <input type=button value='削除' onClick="removeConfirm('<?php echo $row['id']; ?>');"><br>
                        <input type=submit value='作業開始' onClick="document.getElementById('start_id').value='<?php echo $row['id']; ?>';"><br>
                        <input type=submit value='作業終了' onClick="document.getElementById('finish_id').value='<?php echo $row['id']; ?>';">
                    </td>
                    <td>
                        <p class='disp'><?php echo $row['title']; ?></p>
                        <input type=text class='edit' name='title' value='<?php echo $row['title']; ?>'>
                    </td>
                    <td>
                        <p class='disp'><?php echo $row['shosai']; ?></p>
                        <textarea class='edit' name='shosai' cols='40' rows='4'><?php echo $row['shosai']; ?></textarea>
                    </td>
                    <td>
                        <p class='disp'><?php echo $row['needhour_est']."時間".$row['needmin_est']."分"; ?></p>
                        <p class='edit'>
                            <input type='number' class='edit' name='needhour_est' min='0' max='10' value='<?php echo $row['needhour_est']; ?>'>時間
                            <input type='number' class='edit' name='needmin_est' min='0' max='50' step='10' value='<?php echo $row['needmin_est']; ?>'>分
                        </p>
                    </td>
                    <td>
                        <p class='disp'><?php echo $row['start']; ?></p>
                        <input type=text class='edit' name='start' value='<?php echo $row['start']; ?>'>
                    </td>
                    <td>
                        <p class='disp'><?php echo $row['target']; ?></p>
                        <input type=text class='edit' name='target' value='<?php echo $row['target']; ?>'>
                    </td>
                    <td>
                        <p class='disp'><?php echo $row['finish']; ?></p>
                        <input type=text class='edit' name='finish' value='<?php echo $row['finish']; ?>'>
                    </td>
                </tr>
            <?php } ?>
        </table>
        <br>未達成項目<br>
        <table border="1">
            <tr>
                <th>行番号</th>
                <th class="button"></th>
                <th>タイトル</th>
                <th>詳細</th>
                <th class="needtime_est">推定所要時間</th>
                <th>開始日時</th>
                <th>終了予定日時</th>
            </tr>
            <?php foreach($ongoing_result as $row){ ?>
                <tr id='<?php echo "rowid".$row['id']; ?>'>
                    <td><?php echo $row['id']; ?></td>
                    <td>
                        <input type=button id=<?php echo $row['id']; ?> value='編集' onClick="typeChange('<?php echo $row['id']; ?>');">
                        <input type=button value='削除' onClick="removeConfirm('<?php echo $row['id']; ?>');"><br>
                        <input type=submit value='作業開始' onClick="document.getElementById('start_id').value='<?php echo $row['id']; ?>';"><br>
                        <input type=submit value='作業終了' onClick="document.getElementById('finish_id').value='<?php echo $row['id']; ?>';">
                    </td>
                    <td>
                        <p class='disp'><?php echo $row['title']; ?></p>
                        <input type=text class='edit' name='title' value='<?php echo $row['title']; ?>'>
                    </td>
                    <td>
                        <p class='disp'><?php echo $row['shosai']; ?></p>
                        <textarea class='edit' name='shosai' cols='40' rows='4'><?php echo $row['shosai']; ?></textarea>
                    </td>
                    <td>
                        <p class='disp'><?php echo $row['needhour_est']."時間".$row['needmin_est']."分"; ?></p>
                        <p class='edit'>
                            <input type='number' class='edit' name='needhour_est' min='0' max='10' value='<?php echo $row['needhour_est']; ?>'>時間
                            <input type='number' class='edit' name='needmin_est' min='0' max='50' step='10' value='<?php echo $row['needmin_est']; ?>'>分
                        </p>
                    </td>
                    <td>
                        <p class='disp'><?php echo $row['start']; ?></p>
                        <input type=text class='edit' name='start' value='<?php echo $row['start']; ?>'>
                    </td>
                    <td>
                        <p class='disp'><?php echo $row['target']; ?></p>
                        <input type=text class='edit' name='target' value='<?php echo $row['target']; ?>'>
                    </td>
                </tr>
            <?php } ?>
        </table>

        <input type=hidden id='edit_id' name='edit_id' value=''>
        <input type=hidden id='remove_id' name='remove_id' value=''>
        <input type=hidden id='start_id' name='start_id' value=''>
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

<script type="text/javascript">

//初期状態は編集不可
const edit = document.getElementsByClassName('edit');
const disp = document.getElementsByClassName('disp');
for(var i=0; i<edit.length; i++){
    edit[i].style.display = "none";
    edit[i].disabled = 'true';
}
for(var i=0; i<disp.length; i++){
    disp[i].style.display = "block";
}

function typeChange(changeId){
    const rowid = document.getElementById('rowid'+changeId);
    const editrow = rowid.getElementsByClassName('edit');
    const disprow = rowid.getElementsByClassName('disp');
    for(var i=0; i<editrow.length; i++){
        if(edit[i].style.display = "none"){
            editrow[i].style.display = "block";
            editrow[i].disabled = "";
        }else{
            editrow[i].style.display = "none";
        }
    }
    for(var i=0; i<disprow.length; i++){
        if(disprow[i].style.display=="block"){
            disprow[i].style.display = "none";
        }else{
            disprow[i].style.display = "block";
        }
    }

    if(document.getElementById(changeId).value=='編集'){
        document.getElementById(changeId).value = '保存';
    }else{
        document.getElementById(changeId).value = '編集';
        document.getElementById('edit_id').value = changeId;
        document.mymenu_form.submit();
    }
}

function removeConfirm(removeId){
    const conf = window.confirm('行番号'+removeId+'を本当に削除しますか？');
    if(conf == true){
        document.getElementById('remove_id').value = removeId;
        document.mymenu_form.submit();
    }
}
</script>