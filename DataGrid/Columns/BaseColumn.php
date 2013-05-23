<?php
namespace DataGrid;

use Nette\Callback;
use Nette\Utils\Html;
use Nette\Application\UI\Link;
use Nette\ComponentModel\IContainer;
use Orm\IEntity;

/**
 *
 */
abstract class BaseColumn extends \Nette\ComponentModel\Component
{
	/** @var string */
	private $label;

	/** @var Callback|string|Html */
	private $format;

	/** @var bool */
	private $orderable = true;

	/** @var string */
	private $orderingColumn;

	/** @var BaseFilter */
	private $filter;


	/**
	 * @param string
	 * @param Callback|string|Html
	 * @param bool (internal)
	 */
	public function __construct($label)
	{
		$this->label = $label;

		parent::__construct();
	}

	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->label;
	}

	/**
	 * @return Callback|string|Html
	 */
	public function getFormat()
	{
		return $this->format;
	}

	/**
	 * <code>
	 *
	 * $format = '%name%, %id% (%popis%)';
	 * $format = Html::el('b')->setText('%name%');
	 * $format = '%%';
	 *
	 * $format = callback('func');
	 *
	 * /**
	 *  * @param string $value Konkretni sloupec
	 *  * @param IEntity $row Cely radek
	 *  * @return string|Html
	 *  * /
	 * function func($value, IEntity $row);
	 *
	 * </code
	 *
	 * @param Callback|string|Html
	 * @return BaseColumn
	 */
	public function setFormat($format)
	{
		$this->format = $format;

		return $this;
	}

	/**
	 * @param Entity
	 * @return Html|string
	 */
	public function getValue(IEntity $row)
	{
		$name = $this->getName();
		$value = NULL;

		if ($row->hasParam($name) OR isset($row->{$name}))
		{
			$value = $row[$name];
		}
		else if ($this->isOrderable())
		{
			throw new \Nette\InvalidArgumentException($name);
		}

		return $this->applyFormat($value, $this->getFormat(), $row);
	}

	/**
	 * @return bool
	 */
	public function isOrderable()
	{
		return $this->orderable;
	}

	/**
	 * @param bool
	 * @return BaseColumn
	 * @internal
	 */
	public function setOrderable($orderable)
	{
		$this->orderable = (bool) $orderable;

		return $this;
	}

	/**
	 * @param string
	 * @return self
	 */
	public function setOrderingColumn($column)
	{
		$this->orderingColumn = $column;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getOrderingColumn()
	{
		return $this->orderingColumn;
	}

	/**
	 * @param string
	 * @param Callback|string|Html|Link
	 * @param Entity
	 * @return Html|string
	 */
	final protected function applyFormat($value, $format, IEntity $row)
	{

		if ($format === NULL)
		{
			return $value;
		}
		else if ($format instanceof Callback OR $format instanceof \Closure OR function_exists($format))
		{
			return callback($format)->invoke($value, $row);
		}
		else if (is_string($format) OR $format instanceof Link)
		{
			$keys = array(
				'%%' => $value,
			);
			foreach ($row as $k => $v)
			{
				if (!(is_scalar($v) OR (is_object($v) AND method_exists($v, '__toString'))))
				{
					continue;
				}
				$keys['%'.$k.'%'] = (string) $v;
			}

			if (is_string($format))
			{
				return preg_replace_callback('#%([^%]+)%#', function ($m) use ($row) {
					if ($row->hasParameter($m[1]) OR isset($row->{$m[1]})) return $row->{$m[1]};
					return $m[0];
				}, strtr($format, $keys));
			}
			else
			{
				$format = clone $format;
				foreach ($format->getParameters() as $pName => $pValue)
				{
					$ppValue = trim($pValue, '%');
					if (isset($keys[$pValue]))
					{
						$format->setParameter($pName, $keys[$pValue]);
					}
					else if ($pValue{0} === '%' AND ($row->hasParam($ppValue) OR isset($row[$ppValue])))
					{
						$format->setParameter($pName, $row->{$ppValue});
					}
				}
				return $format;
			}
		}
		else if ($format instanceof Html)
		{
			// todo xss problem u vkladanejch hodnot
			return Html::el()->setHtml($this->applyFormat($value, (string) $format, $row));
		}


		throw new \Nette\InvalidStateException;

	}

	/**
	 * @param  IComponentContainer
	 * @throws \Nette\InvalidStateException
	 */
	protected function validateParent(IContainer $parent)
	{
		if (!($parent instanceof \DataGrid\Grid))
		{
			throw new \Nette\InvalidStateException;
		}
	}

	public function setTextFilter()
	{
		$this->getParent()->filters[$this->getName()] = $filter = new TextFilter;
		return $filter;
	}

	public function setSelectFilter(array $items)
	{
		$this->getParent()->filters[$this->getName()] = $filter = new SelectFilter($items);
		return $filter;
	}

	public function setLetterFilter(array $usedChars = NULL)
	{
		$this->getParent()->filters[$this->getName()] = $filter = new LetterFilter($usedChars);
		return $filter;
	}

}
