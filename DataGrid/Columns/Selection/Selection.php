<?php
namespace DataGrid;

use Nette;

/**
 *
 */
class Selection extends Nette\Application\UI\Control implements \IClientRenderable
{
	/** @var SelectionColumn */
	public $column;

	public static function getClientFiles()
	{
		return dirname(__FILE__) . '/selection.js';
	}

	public function render()
	{
		$this->template->setFile(dirname(__FILE__) . '/selection.latte');
		$this->template->column = $this->column;
		$this->template->data = $this->lookup('DataGrid\\Grid')->getDataSource()->findById($this->getSelected());
		$this->template->render();
	}

	public function getSelected()
	{
		return array_filter(iterator_to_array($this->getSession()));
	}

	protected function getSession()
	{
		return $this->presenter->getSession(__CLASS__);
	}

	public function handleSelect($id)
	{
		$this->getSession()->{$id} = $id;
		if (!$this->presenter->isAjax()) $this->redirect('this');
		$this->invalidateControl('selection');
	}

	public function handleDeselect($id)
	{
		unset($this->getSession()->{$id});
		if (!$this->presenter->isAjax()) $this->redirect('this');
		$this->invalidateControl('selection');
	}

	public function handleClear()
	{
		$this->getSession()->remove();
		$grid = $this->lookup('Grid');
		if ($grid->filter AND $grid->filter[$this->column->name])
		{
			unset($grid->filter[$this->column->name]);
		}
		$this->invalidateControl('selection');
		if (!$this->presenter->isAjax()) $this->redirect('this');
		$this->invalidateControl('selection');
	}

	public function handleShow()
	{
		$this->lookup('Grid')->filter = array($this->column->name => array('text' => 1));
		$this->redirect('this');
	}

	public function isSelected($id)
	{
		return isset($this->getSession()->{$id});
	}

}
