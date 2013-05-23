<?php
namespace DataGrid;

use Orm\IEntityCollection;
use Nette\InvalidStateException;


/**
 *
 */
class FulltextFilter extends TextFilter
{
	private $label;

	public function __construct($label)
	{
		$this->label = $label;
		parent::__construct();
	}

	protected function defaultFilter(IEntityCollection $dataSource, $value)
	{
		throw new InvalidStateException();
	}

	/** @return string */
	public function getLabel()
	{
		return $this->label;
	}
}
