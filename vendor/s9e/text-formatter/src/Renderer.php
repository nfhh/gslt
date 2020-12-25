<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;
use DOMDocument;
use InvalidArgumentException;
abstract class Renderer
{
	protected $params = [];
	protected function loadXML($xml)
	{
		$this->checkUnsupported($xml);
		$flags = (\LIBXML_VERSION >= 20700) ? \LIBXML_COMPACT | \LIBXML_PARSEHUGE : 0;
		$useErrors = \libxml_use_internal_errors(\true);
		$dom       = new DOMDocument;
		$success   = $dom->loadXML($xml, $flags);
		\libxml_use_internal_errors($useErrors);
		if (!$success)
			throw new InvalidArgumentException('Cannot load XML: ' . \libxml_get_last_error()->message);
		return $dom;
	}
	public function render($xml)
	{
         if (\substr($xml, 0, 3) === '<t>' && \substr($xml, -4) === '</t>'){
            return $this->renderPlainText($xml);
        } else {
            return $this->renderRichText(\preg_replace('(<[eis]>[^<]*</[eis]>)', '', $xml));
        }
	}
	protected function renderPlainText($xml)
	{
        // 列表展示和添加&编辑预览兜底 手动解析下艾特纯文本
        if (strpos($xml, '[/at]') !== false) {

            $xml = str_replace('[/at]<br/>','[/at]',$xml);

            preg_match("/(?<=at=)(.*?)(?=\s)/i", $xml, $matches);
            $user_name = $matches[0];

            preg_match("/(?<=post_id=)(.*?)(?=\s)/i", $xml, $matches);
            $post_id = $matches[0];

            preg_match("/(?<=time=)(.*?)(?=\s)/i", $xml, $matches);
            $time = $matches[0];

            preg_match("/(?<=user_id=)(.*?)(?=])/i", $xml, $matches);
            $user_id = $matches[0];

            $xml = str_replace('<br>', '', $xml);
            $content = preg_replace('/\[at(.*)at\]/i', '', $xml);

            return <<<str
<div class="myat bbb"><strong><i class="icon fa-at fa-fw ftw" aria-hidden="true"></i><span class="sr-only">{L_BUTTON_AT}</span></strong><a href="./memberlist.php?mode=viewprofile&u=$user_id">$user_name</a>
</div>
<div>$content</div>
str;
        }
		$html = \substr($xml, 3, -4);
		$html = \str_replace('<br/>', '<br>', $html);
		$html = $this->decodeSMP($html);
		return $html;
	}
	abstract protected function renderRichText($xml);
	public function getParameter($paramName)
	{
		return (isset($this->params[$paramName])) ? $this->params[$paramName] : '';
	}
	public function getParameters()
	{
		return $this->params;
	}
	public function setParameter($paramName, $paramValue)
	{
		$this->params[$paramName] = (string) $paramValue;
	}
	public function setParameters(array $params)
	{
		foreach ($params as $paramName => $paramValue)
			$this->setParameter($paramName, $paramValue);
	}
	protected function checkUnsupported($xml)
	{
		if (\strpos($xml, '<!') !== \false)
			throw new InvalidArgumentException('DTDs, CDATA nodes and comments are not allowed');
		if (\strpos($xml, '<?') !== \false)
			throw new InvalidArgumentException('Processing instructions are not allowed');
	}
	protected function decodeSMP($str)
	{
		if (\strpos($str, '&#') === \false)
			return $str;
		return \preg_replace_callback('(&#(?:x[0-9A-Fa-f]+|[0-9]+);)', __CLASS__ . '::decodeEntity', $str);
	}
	protected static function decodeEntity(array $m)
	{
		return \htmlspecialchars(\html_entity_decode($m[0], \ENT_QUOTES, 'UTF-8'), \ENT_COMPAT);
	}
}