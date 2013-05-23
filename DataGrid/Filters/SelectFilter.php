<?php
namespace DataGrid;


/**
 *
 */
class SelectFilter extends BaseFilter
{
	private $items;

	public function __construct(array $items)
	{
		$this->items = $items;
		parent::__construct();
	}

	/** @return string|NULL */
	public function getReadableValue()
	{
		return $this['text']->getSelectedItem();
	}

	protected function create()
	{
		$this->addSelect('text', NULL, $this->items)
			->setPrompt('');
	}

}
