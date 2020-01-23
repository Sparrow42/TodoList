<pre>
<?php
print_r($_POST);
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
　　→〇・表の日時を時間表示にする。
〇・id仕様からクラス仕様に整理する（大きく改変必要。やるなら早めにやっておきたい。）
　…〇・イメージは、行ごとにdivで異なるid付与、クラスはdispとeditの２つのみ付与。
　　divのid指定からのclass指定により、指定行のみの操作が可能になるはず。
　　…trへのid指定により完了。divは効かない。
〇・作業開始ボタンの追加。
〇・入力欄大きく。新規登録と編集の詳細欄。
〇・時間指定しやすくする。
　…input=timeで対応する、
〇・時間指定しない入力パターンも必要。
〇・重要度、推定所要時間のカラムを作る。
　→〇・regとmenuにも入力・編集・表示を対応させる。
　　　…regは完了。menuは所要時間が完了、重要度も完了。
〇・重要度を表示させる。
〇・表に日にちいらない。
・メモ欄の一時保存を遷移時に必ずするように。
〇・作業開始ボタンつける。
・表のずれを直す。
　…幅の問題でcssを変えても変化しない。日時などを省略してから。
・進行度ステータス（progress_flag）を作る。
・menuで作業順の入力できるようにする。
〇・優先順位の設定。
途・並び順を重要度・作業順に変更する。

＜優先（中）＞
・継続Todoを設定する（継続フラグ追加。表を別で表示する。）
・リストの「アーカイブ保存」ボタンを追加（表から非表示。アーカイブフラグ追加。）
〇・表のタイトルから終了日時のループをhtmlに直す。(regとmenuどちらも)
・登録日を作る。作業開始日とは別。
〇・作業開始を押すと、終了予定日時が自動入力される。
　…ついでにオブジェクト指向にした。
・目的、PDCAなどの項目追加（詳細とメモ欄で代用はできている）
 …詳細の登録デフォルト設定で対応可。こちらのほうが柔軟にできる。
〇・多分0時0分で登録すると表示されないバグある。

＜優先（低）＞
・typeChangeのeditとdispの整理。
・作業中断に対応させる。
・作業実績の表示
・作業実績の分析
・時刻入力方法の改善
・デザインの改善　…　bootstrapなど
・メモアーカイブのDB化
・複数ユーザーへの対応。
　…会員登録機能。管理者画面の追加。
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
    $edit_id = $_POST['edit_id'];
    $sql = "UPDATE todo_master 
    SET title=:title, shosai=:shosai, start=:start, target=:target, 
    finish=:finish, needtime_est=:needtime_est, needhour_est=:needhour_est, 
    needmin_est=:needmin_est, juyodo=:juyodo 
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
    $stmt -> bindParam(':juyodo', $_POST['juyodo'.$edit_id], PDO::PARAM_STR);
    $stmt -> bindParam(':edit_id', $edit_id, PDO::PARAM_STR);
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

    //終了予定日時の計算
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

//優先順位の更新
if(!empty($_POST['priority_update'])){
    $priority = $_POST['priority'];
    foreach($priority as $key => $value){
        $sql = "UPDATE todo_master SET ";
        $sql.= "priority='".$value;
        $sql.= "' WHERE id=".$key;
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }
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
    $ontable_memo.= $date->format('Y年m月d日');
}

$sql = "SELECT * FROM todo_master;";
$stmt = $pdo->query($sql);
$non_input='0000-00-00 00:00:00';
$ongoing_result=[];
$finished_result=[];
$result=[];
$ongoing_cnt=0;
$finished_cnt=0;
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

    //時分表示用
    $start_date = new DateTime($row['start']);
    $target_date = new DateTime($row['target']);
    $finish_date = new DateTime($row['finish']);
    $row['start_Hi'] = $start_date->format('H:i');
    $row['target_Hi'] = $target_date->format('H:i');
    $row['finish_Hi'] = $finish_date->format('H:i');

    //指定なし時刻非表示用
    if($row['start']==$non_input) $row['start_Hi']="";
    if($row['target']==$non_input) $row['target_Hi']="";
    if($row['finish']==$non_input){
        $row['finish_Hi']="";
        $ongoing_cnt++;
        $ongoing_result[]=$row;
    }else{
        $finished_cnt++;
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
        <!--<link rel="stylesheet" type="text/css" href="css/table.css">-->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery.tablesorter/2.31.0/css/theme.default.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery.tablesorter/2.31.0/css/theme.blue.min.css">
        <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery.tablesorter/2.31.0/js/jquery.tablesorter.min.js"></script>
        <!-- 追加機能(widgets)を使用する場合は次も追加する -->
        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery.tablesorter/2.31.0/js/jquery.tablesorter.widgets.min.js"></script>
    </head>
    <h2>Todoリスト　マイメニュー</h2>
    <h3>今日のTodo</h3>
    <form method='post' name='mymenu_form' action='mytodo_menu.php'>
        <a href='mytodo_register.php'>新規登録</a><br>
        <br>達成済み項目<br>
        <table id="myTableFinished" class="tablesorter tablesorter-blue">
            <thead>
                <tr>
                    <th>行番号</th>
                    <th class="button"></th>
                    <th>タイトル</th>
                    <th>詳細</th>
                    <th>重要度</th>
                    <th class="needtime_est">推定所要時間</th>
                    <th>開始日時</th>
                    <th>終了予定日時</th>
                    <th>終了日時</th>
                </tr>
            </thead>
            <tbody>
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
                        <p class='disp'><?php echo $row['juyodo']; ?></p>
                        <p class='edit'>
                            <input type='radio' class='edit' name='juyodo<?php echo $row['id']; ?>' value='高'>高
                            <input type='radio' class='edit' name='juyodo<?php echo $row['id']; ?>' value='中'>中
                            <input type='radio' class='edit' name='juyodo<?php echo $row['id']; ?>' value='低'>低
                        </p>
                        <input type=hidden id='juyodo_checked<?php echo $row['id']; ?>' value='<?php echo $row['juyodo']; ?>'>
                    </td>
                    <td>
                        <p class='disp'><?php echo $row['needhour_est']."時間".$row['needmin_est']."分"; ?></p>
                        <p class='edit'>
                            <input type='number' class='edit' name='needhour_est' min='0' max='10' value='<?php echo $row['needhour_est']; ?>'>時間
                            <input type='number' class='edit' name='needmin_est' min='0' max='50' step='10' value='<?php echo $row['needmin_est']; ?>'>分
                        </p>
                    </td>
                    <td>
                        <p class='disp'><?php echo $row['start_Hi']; ?></p>
                        <input type=text class='edit' name='start' value='<?php echo $row['start']; ?>'>
                    </td>
                    <td>
                        <p class='disp'><?php echo $row['target_Hi']; ?></p>
                        <input type=text class='edit' name='target' value='<?php echo $row['target']; ?>'>
                    </td>
                    <td>
                        <p class='disp'><?php echo $row['finish_Hi']; ?></p>
                        <input type=text class='edit' name='finish' value='<?php echo $row['finish']; ?>'>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        <br>未達成項目<br>
        <table id="myTableOngoing" class="tablesorter tablesorter-blue">
            <thead>
                <tr>
                    <th>行番号</th>
                    <th class="button"></th>
                    <th>タイトル</th>
                    <th>詳細</th>
                    <th>重要度</th>
                    <th class="needtime_est">推定所要時間</th>
                    <th>優先順位<input type=submit class='priority' name='priority_update' value='更新'></th>
                    <th>開始日時</th>
                    <th>終了予定日時</th>
                </tr>
            </thead>
            <tbody>
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
                        <p class='disp'><?php echo $row['juyodo']; ?></p>
                        <p class='edit'>
                            <input type='radio' class='edit' name='juyodo<?php echo $row['id']; ?>' value='高'>高
                            <input type='radio' class='edit' name='juyodo<?php echo $row['id']; ?>' value='中'>中
                            <input type='radio' class='edit' name='juyodo<?php echo $row['id']; ?>' value='低'>低
                        </p>
                        <input type=hidden id='juyodo_checked<?php echo $row['id']; ?>' value='<?php echo $row['juyodo']; ?>'>
                    </td>
                    <td>
                        <p class='disp'><?php echo $row['needhour_est']."時間".$row['needmin_est']."分"; ?></p>
                        <p class='edit'>
                            <input type='number' class='edit' name='needhour_est' min='0' max='10' value='<?php echo $row['needhour_est']; ?>'>時間
                            <input type='number' class='edit' name='needmin_est' min='0' max='50' step='10' value='<?php echo $row['needmin_est']; ?>'>分
                        </p>
                    </td>
                    <td>
                        <select class='priority' name="priority[<?php echo $row['id']; ?>]">
                            <option value="99"></option>
                        </select>
                        <input type=hidden name='priority_num[]' value='<?php echo $row['priority']; ?>'>
                    </td>
                    <td>
                        <p class='disp'><?php echo $row['start_Hi']; ?></p>
                        <input type=text class='edit' name='start' value='<?php echo $row['start']; ?>'>
                    </td>
                    <td>
                        <p class='disp'><?php echo $row['target_Hi']; ?></p>
                        <input type=text class='edit' name='target' value='<?php echo $row['target']; ?>'>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
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

$(function() {
    $("#myTableFinished").tablesorter({ 
        sortList: [[0,0], [1,0]],
        headers: { 
          1: {sorter:false},
          2: {sorter:false},
          3: {sorter:false}
        } 
    });
});

$(function() {
    $("#myTableOngoing").tablesorter({ 
        sortList: [[0,0], [1,0]], 
        headers: { 
          1: {sorter:false},
          2: {sorter:false},
          3: {sorter:false}
        }
    });
});

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

//優先順位のオプション設定
const priorityClass = document.getElementsByClassName('priority'); //フォームと直接紐づくクラス
const priorityNum = document.getElementsByName('priority_num[]'); //データベースから取ってきたpriority
const optionNum = <?php echo $ongoing_cnt; ?>;
for(var j=1; j<priorityClass.length; j++){
    for(var i=1; i<=optionNum; i++){
        var option = document.createElement("option");
        option.text = i;
        option.value = i;
        priorityClass[j].appendChild(option);
    }
}
//優先順位の既定値設定
//priorityNumの数値とoptionのvalueが一致したら、クラスのpriorityClass.item(i+1).options[j].selected= 'true'をする。
for(var i=0; i<priorityNum.length; i++){
    for(var j=1; j<optionNum+1; j++){
        if(priorityNum[i].value == priorityClass.item(1).options[j].value){
            priorityClass[i+1].options[j].selected= 'true';
        }
    }
}

function typeChange(changeId){
    /***************************************************
    編集していないとき
    … class=disp -> disprow[i].style.display = "block";
      class=edit -> editrow[i].style.display = "none";
                    edit[i].disabled = 'true';
    編集中 
    … class=disp -> disprow[i].style.display = "none";
      class=edit -> editrow[i].style.display = "block";
                    editrow[i].disabled = "";
    ****************************************************/

    const rowid = document.getElementById('rowid'+changeId);
    const editrow = rowid.getElementsByClassName('edit');
    const disprow = rowid.getElementsByClassName('disp');
    //下のif文に合成したほうが分かりやすいかも
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
        
        //重要度ボタンの既定値設定
        const juyodoCheckedValue = document.getElementById('juyodo_checked'+changeId).value;
        const juyodoName = document.getElementsByName('juyodo'+changeId);
        for(var i=0; i<juyodoName.length; i++){
            if(juyodoName[i].value == juyodoCheckedValue){
                juyodoName[i].checked = true;
            }
        }

        //優先順位の編集不可設定
        const priority = document.getElementsByClassName('priority');
        for(var i=0; i<priority.length; i++){
            priority[i].disabled = 'true';
        }

    }else{
        document.getElementById(changeId).value = '編集';
        document.getElementById('edit_id').value = changeId;

        //優先順位の編集可能設定
        const priority = document.getElementsByClassName('priority');
        for(var i=0; i<priority.length; i++){
            priority[i].disabled = '';
        }

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