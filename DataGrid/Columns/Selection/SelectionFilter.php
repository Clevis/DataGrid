<?php
namespace DataGrid;

use Orm;


/**
 *
 */
class SelectionFilter extends BaseFilter
{
	/** @var Selection */
	private $selection;

	public function __construct(Selection $selection)
	{
		$this->selection = $selection;
		parent::__construct();
	}

	/** @return string|NULL */
	public function getReadableValue()
	{
		return count($this->selection->getSelected()) . ' vybraných';
	}

	protected function create()
	{
		$this->addCheckbox('text', false)->getControlPrototype()->addClass('selection');
	}

	protected function defaultFilter(Orm\IEntityCollection $dataSource, $value)
	{
		return $dataSource->findById($this->selection->getSelected());
	}

	public function isActive()
	{
		return $this['text']->value;
	}

	public function getLabel()
	{
		return 'Výběr';
	}

}
