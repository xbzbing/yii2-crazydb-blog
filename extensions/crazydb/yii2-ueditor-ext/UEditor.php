<?php
/**
 * Usage:
 * <?=\djfly\kindeditor\KindEditor::widget([
 *   'id'=>'Article_content',	# Textarea id
 *
 *   # Additional Parameters (Check http://www.kindsoft.net/docs/option.html)
 *   'items' => [
 *       'width'=>'700px',
 *       'height'=>'300px',
 *       'themeType'=>'simple',
 *       'allowFileManager'=>false,
 *       'allowImageUpload'=>false,
 *       'items'=>[
 *           'fontname', 'fontsize', '|', 'forecolor', 'hilitecolor', 'bold', 'italic', 'underline',
 *           'removeformat', '|', 'justifyleft', 'justifycenter', 'justifyright', 'insertorderedlist',
 *           'insertunorderedlist', '|', 'emoticons', 'image', 'link',
 *   ]],
 * ]); ?>
 */

/**
 * KindEditor InputWidget.
 */
namespace crazydb\ueditor;
use yii;

class UEditor extends yii\widgets\InputWidget
{
	public $language = '';

	/**
	 * @var array the ueditor items configuration.
	 */
	public $items = array();

	/**
	 * Initializes the widget.
	 */
	public function init()
	{
		parent::init();
        Yii::setAlias('crazydb','@app/extensions/crazydb');
		Yii::setAlias('@crazydb/ueditor','@crazydb/yii2-ueditor-ext');
	}

	/**
	 * Runs the widget.
	 */
	public function run()
	{
		$script = '';
        $script .= 'console.debug("hello world")';
		$this->getView()->registerJs($script);
		UEditorAsset::register($this->getView());
	}
}
