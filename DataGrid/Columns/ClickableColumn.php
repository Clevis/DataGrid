<?php
namespace DataGrid;

use Nette\Utils\Html;
use Nette\Application\UI\Link;
use Orm\IEntity;


/**
 *
 */
class ClickableColumn extends BaseColumn
{
	/** @var Link */
	private $destination;

	/**
	 * @param string
	 * @param Link
	 * @param Callback|string|Html
	 * @param bool (internal)
	 */
	public function __construct($label, Link $destination)
	{
		$this->destination = $destination;
		parent::__construct($label);
	}


	/**
	 * @param Entity
	 * @return Html|string
	 */
	public function getValue(IEntity $row)
	{
		$content = parent::getValue($row);

		$destination = $this->applyFormat(NULL, $this->getDestination(), $row);

		$a = Html::el('a')
			->href($destination);

		return $content instanceof Html ? $a->add($content) : $a->setText($content);
	}

	/**
	 * @return Link
	 */
	protected function getDestination()
	{
		return $this->destination;
	}

}
