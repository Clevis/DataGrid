<?php
namespace DataGrid;

use Nette;
use Nette\Callback;
use Nette\InvalidStateException;
use Orm\IEntityCollection;


/**
 *
 */
abstract class BaseFilter extends Nette\Forms\Container
{

	private $callback;

	public function __construct()
	{
		parent::__construct();
		$this->create();
	}

	/** @return string|NULL */
	abstract public function getReadableValue();

	abstract protected function create();

	final public function render()
	{
		foreach ($this->getControls() as $control)
		{
			echo $control->getControl();
		}
	}

	public function applyFilter(IEntityCollection $dataSource)
	{
		if ($this->isActive())
		{
			$value = $this['text']->getValue();
			return $this->filter($dataSource, $value);
		}
		return $dataSource;
	}

	final protected function filter(IEntityCollection $dataSource, $value)
	{
		if ($this->callback)
		{
			$ds = $this->callback->invoke($dataSource, $value, $this);
		} else {
			$ds = $this->defaultFilter($dataSource, $value);
		}
		if (!($ds instanceof IEntityCollection)) throw new InvalidStateException();
		return $ds;
	}

	final public function setCustomFilter(Callback $callback)
	{
		$this->callback = $callback;
		return $this;
	}

	protected function defaultFilter(IEntityCollection $dataSource, $value)
	{
		return $dataSource->{"findBy{$this->name}"}($value);
	}

	/** @return bool */
	public function isActive()
	{
		$value = $this['text']->getValue();
		if ($value !== '' AND $value !== NULL)
		{
			return true;
		}
		return false;
	}

	/** @return string */
	public function getLabel()
	{
		$grid = $this->lookup('Grid');
		return $grid[$this->getName()]->getLabel();
	}

}
