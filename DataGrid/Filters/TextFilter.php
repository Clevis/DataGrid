<?php
namespace DataGrid;

use Orm\IEntityCollection;
use Nette\NotSupportedException;


/**
 *
 */
class TextFilter extends BaseFilter
{

	/** @return string|NULL */
	public function getReadableValue()
	{
		return $this['text']->getValue();
	}

	protected function create()
	{
		$this->addText('text')
			->getControlPrototype()->autocomplete('off');
	}

	protected function defaultFilter(IEntityCollection $dataSource, $value)
	{
		if ($dataSource instanceof \DibiDataSource)
		{
			return $dataSource->where('%n LIKE %~like~', $this->name, $value)->toDataSource();
		}
		else
		{
			throw new NotSupportedException();
		}
	}

}
