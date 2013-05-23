<?php
namespace DataGrid;

use Orm;
use Nette\Utils\Html;


/**
 *
 */
class SelectionColumn extends BaseColumn
{
	/** @var Selection */
	private $selection;

	public function __construct(Selection $selection)
	{
		$this->selection = $selection;
		$this->selection->column = $this;
		$this->monitor('Grid');
		parent::__construct('');
	}

	protected function attached($grid)
	{
		parent::attached($grid);
		if ($grid instanceof Grid)
		{
			$grid->filters[$this->getName()] = $filter = new SelectionFilter($this->selection);
		}
	}

	public function isOrderable()
	{
		return false;
	}

	public function getHeaderValue(Orm\IEntity $row)
	{
		return $this->applyFormat($row->id, $this->getFormat(), $row);
	}

	public function getValue(Orm\IEntity $row)
	{
		return Html::el('input', array(
			'type' => 'checkbox',
			'class' => 'selection',
			'checked' => $this->selection->isSelected($row->id),
			'data-id' => $row->id,
			'data-select' => $this->selection->link('select', $row->id),
			'data-deselect' => $this->selection->link('deselect', $row->id),
		));
	}

}
