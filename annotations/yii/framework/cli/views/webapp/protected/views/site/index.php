<style>
    td{padding:5px;}
    .annotations{color:red;}
</style>
<?php
$this->pageTitle = Yii::app()->name . ' - GroupDocs';
$this->breadcrumbs = array(
    'GroupDocs Annotation',
);
?>

<h1>GroupDocs Annotation</h1>

<?php 
    //db installation result
    if(isset($res)){?>
    <div class="flash-success">
        <?php echo $res; ?>
    </div>
<?php } ?>

<?php if (Yii::app()->user->hasFlash('index')): ?>

    <div class="flash-success">
        <?php echo Yii::app()->user->getFlash('index'); ?>
    </div>

<?php else: ?>

    <p>
        If you upload the local file, please enter your: "<b>Client ID</b>" and "<b>Private Key</b>". You can find them at your GroupDocs Acc. Dashboard
    </p>

    <div class="form">

        <?php
        $form = $this->beginWidget('CActiveForm', array(
                    'id' => 'groupdocs-form',
                    'enableClientValidation' => false,
                    'htmlOptions' => array('enctype' => 'multipart/form-data'),
                ));
        ?>


    <?php echo $form->errorSummary($model); ?>

        <table style="width:350px;border:3px double gray;">
            <tr style="border:1px solid green;">
                <td style="border:1px solid red;">

                    <?php echo $form->labelEx($model, 'client_id'); ?>
                    <?php echo $form->textField($model, 'client_id'); ?>
                    <?php echo $form->error($model, 'client_id'); ?>
                </td>
                <td colspan="2" style="border:1px solid red;">
                    <?php echo $form->labelEx($model, 'api_key'); ?>
                    <?php echo $form->textField($model, 'api_key',array('htmlOptions'=>"style='width:250px;'")); ?>
                    <?php echo $form->error($model, 'api_key'); ?>
                </td>
            </tr>
            <tr>
                <td>
                    <?php echo $form->labelEx($model, 'file_id'); ?>
                    <?php echo $form->textField($model, 'file_id'); ?>
                    <?php echo $form->error($model, 'file_id'); ?>
                </td>
                <td>OR</td>
                <td>
                    <?php echo $form->labelEx($model, 'file'); ?>
                    <?php echo $form->fileField($model, 'file'); ?>
                    <?php echo $form->error($model, 'file'); ?>
                </td>
            </tr>
        </table>
        
    <?php echo CHtml::submitButton('Submit'); ?>

    <?php $this->endWidget(); ?>
    </div><!-- form -->
<?php endif; ?>


<?php 
if($iframe){
    // Pull Annotations button ?>
    <hr/>
    <input type="button" value="Pull Annotations" onclick="pullAnnot('<?=$model->client_id?>','<?=$model->api_key?>','<?=$model->file?>')"/>
    <div class="annotations"></div>
    <?php
    // Iframe
    print '<hr/>'.$iframe;
}
?>

<script>
    function pullAnnot(client_id,api_key,file_id){
        if(!file_id) {
            $('iframe').each(function(key,ob) {
                var str = ob.src;
                var strSrc= str.split("/");
                var srcFileId = strSrc[5];
                file_id = srcFileId;// cos one iframe exists 
            });
        }
        $.ajax({
            url: "/?r=site/pullannot&client_id="+client_id+"&api_key="+api_key+"&file_id="+file_id,
            type: "GET",
            dataType: "html",
            success: function(data){
                $(".annotations").html(data);		
            }
        });
    }
</script>