<?php

namespace DataGrid;

use Nette;
use Nette\Callback;
use Nette\ComponentModel\IContainer;
use Nette\ComponentModel\IComponent;
use Nette\Utils\Paginator;
use Nette\Application\UI\Link;
use Nette\Application\UI\Form;
use Nette\InvalidStateException;

use Orm\IEntityCollection;


/**
 * Filterable, orderable data grid for Orm
 */
class Grid extends Nette\Application\UI\Control
{

	/** @persistent int */
	public $page = 1;

	/** @persistent string null means default */
	public $order;

	/** @persistent string */
	public $orderDir = self::ASC;

	/** @var string */
	public $defaultOrder;

	/** @var string precedes user ordering */
	public $primaryOrder;

	/** @var string */
	public $primaryOrderDir = self::ASC;

	/** @persistent int null means default */
	public $itemsPerPage = NULL;

	/** @persistent array values from form */
	public $filter;

	/** @var int 0 means all */
	public $defaultItemsPerPage = 30;

	/** @var IEntityCollection */
	private $dataSource;

	/** @var Paginator */
	private $paginator;

	private $filteredDataSource;

	/**#@+ Order directory */
	const ASC = \Dibi::ASC;
	const DESC = \Dibi::DESC;
	/**#@-*/

	public function __construct(IContainer $parent = NULL, $name = NULL)
	{
		$this->paginator = new Paginator;
		parent::__construct($parent, $name);
	}

	/**
	 * @param IEntityCollection
	 */
	public function bindDataTable(IEntityCollection $dataSource)
	{
		$this->dataSource = $dataSource;
	}

	/**
	 * @return IEntityCollection
	 */
	public function getDataSource()
	{
		if (!($this->dataSource instanceof IEntityCollection))
		{
			throw new InvalidStateException('Data source has not been set.');
		}

		return $this->dataSource;
	}

	/**
	 * @param BaseColumn
	 * @param string $name db column name
	 * @return BaseColumn
	 */
	protected function addColumn(BaseColumn $column, $name)
	{
		if ($name === NULL)
		{
			$name = count($this->getColumns());
			$column->setOrderable(false);
		}

		return $this[$name] = $column;
	}


	/**
	 * @param string $name db column name
	 * @param string
	 * @return ClickableColumn
	 */
	public function addText($name, $label)
	{
		return $this->addColumn(new TextColumn($label), $name);
	}

	/**
	 * @param string $name db column name
	 * @param string
	 * @param Link
	 * @return ClickableColumn
	 */
	public function addClickable($name, $label, Link $destination)
	{
		return $this->addColumn(new ClickableColumn($label, $destination), $name);
	}

	/**
	 * @param string
	 * @return string
	 * @deprecated ?
	 */
	public function getColumnLabel($name)
	{
		return isset($this[$name]) ? $this[$name]->getLabel() : NULL;
	}

	/**
	 * Kolik polozek ma byt na stranku?
	 * @return int
	 */
	public function getItemPerPage()
	{
		$this->defaultItemsPerPage = (int) $this->defaultItemsPerPage;
		return $this->itemsPerPage === NULL ? $this->defaultItemsPerPage : $this->itemsPerPage;
	}

	/**
	 * @internal
	 * @param string
	 * @param string self::ASC self::DESC
	 */
	public function handleOrder($by, $dir = NULL)
	{
		if ($this->order === $by)
		{
			if ($dir === NULL)
			{
				if ($this->orderDir === self::ASC)
				{
					$this->orderDir = self::DESC;
				}
				else if ($this->orderDir === self::DESC)
				{
					$this->order = NULL;
					$this->orderDir = self::ASC;
				}
			}
			else
			{
				$this->orderDir = $dir === self::DESC ? self::DESC : self::ASC;
			}
		}
		else
		{
			$this->order = $by;
			$this->orderDir = $dir === self::DESC ? self::DESC : self::ASC;
		}

		$this->redirect('this');
	}

	/**
	 * @internal
	 * @param int
	 */
	public function handlePage($page)
	{
		$this->page = max((int) $page, 1);
		$this->redirect('this');
	}

	/**
	 * @internal
	 */
	public function handleNext()
	{
		$this->page++;
		$this->redirect('this');
	}

	/**
	 * @internal
	 */
	public function handlePrev()
	{
		$this->page--;
		$this->redirect('this');
	}

	/**
	 * @param int|NULL null means all
	 * @internal
	 */
	public function handleItems($number = 0)
	{
		$this->page = 1;
		$this->itemsPerPage = ($number === NULL OR $number === $this->defaultItemsPerPage) ? NULL : max((int) $number, 0);
		$this->redirect('this');
	}

	public function handleFilter(Form $form)
	{
		$this->filter = $form->getValues();
		$this->redirect('this');
	}

	/**
	 * @return \Nette\Iterators\InstanceFilter
	 */
	private function getColumns()
	{
		$iterator = $this->getComponents(false, 'DataGrid\BaseColumn');

		return $iterator;
	}

	/**
	 * @return \Iterator
	 */
	private function getIterator()
	{
		$itemPerPage = $this->getItemPerPage();

		/** @var \DibiDataSource */
		$dataSource = $this->getFilteredDataSource();

		$this->paginator->setPage($this->page);
		$this->paginator->setItemCount(count($dataSource));
		$this->paginator->setItemsPerPage(!$itemPerPage ? $this->paginator->getItemCount() : $itemPerPage);
		$this->page = $this->paginator->getPage();

		if ($itemPerPage)
		{
			$dataSource->applyLimit($this->paginator->getLength(), $this->paginator->getOffset());
		}

		if ($this->primaryOrder)
		{
			$dataSource->orderBy($this->primaryOrder, $this->primaryOrderDir);
		}

		if ($this->order)
		{
			$dataSource->orderBy($this[$this->order]->getOrderingColumn() ?: $this->order, $this->orderDir);
		}
		else if ($this->defaultOrder)
		{
			$dataSource->orderBy($this->defaultOrder, $this->orderDir);
		}

		// todo search

		return $dataSource->getIterator();
	}

	/**
	 * Render control
	 */
	public function render()
	{
		$this->template->paginator = $this->paginator;

		$this->template->data = $this->getIterator();

		$this->template->columns = $this->getColumns();

		if (!count($this->template->columns))
		{
			foreach (array_keys(iterator_to_array($this->getDataSource()->fetch())) as $name)
			{
				$this->addText($name, ucfirst($name));
				echo "$name/";
			}

			$this->template->columns = $this->getColumns();
		}

		$this->template->grid = $this;
		$this->template->render();
	}

	/**
	 * @return \Nette\Templating\FileTemplate
	 */
	protected function createTemplate($class = NULL)
	{
		return parent::createTemplate()
			->setFile(dirname(__FILE__) . '/grid.latte')
		;
	}

	/**
	 * @param IComponent
	 * @throws InvalidStateException
	 */
	protected function validateChildComponent(IComponent $child)
	{
		/*if (isset($this->filteredDataSource))
		{
			throw new InvalidStateException('Filtrace se jiz aplikaovala neni mozne pridavat dalsi polozky.');
		}*/

		do {
			if ($child instanceof BaseColumn) break;
			if ($child instanceof Form) break;
			if ($child instanceof Selection) break;
			throw new InvalidStateException;
		} while (false);
	}

	/**
	 * @return Form
	 */
	public function getFilters()
	{
		if (!$this->getComponent('filters', false))
		{
			$this['filters'] = new Form;
			$this['filters']->addSubmit('___filter', 'Filter');
			$this['filters']->addSubmit('___clear', 'Clear')->onClick[] = new Callback(function ($b)
			{
				$b->form->parent->redirect("this", array("filter" => NULL));
			});

			$this['filters']->onSuccess[] = callback($this, 'handleFilter');
		}
		return $this['filters'];
	}

	/**
	 * @return bool
	 */
	public function hasFilter()
	{
		return (bool) $this->getComponent('filters', false);
	}

	/**
	 * @return bool
	 */
	public function hasActiveFilter()
	{
		if (!$this->hasFilter() OR !$this->filter) return false;
		return (bool) array_filter($this->filter, 'array_filter');
	}

	/**
	 * @return IEntityCollection
	 */
	public function getFilteredDataSource()
	{
		if (!isset($this->filteredDataSource))
		{
			$this->filteredDataSource = $this->applyFilter(clone $this->getDataSource())/*->toDataSourceCollection()*/;
		}
		return clone $this->filteredDataSource;
	}

	/**
	 * @param IEntityCollection
	 * @return IEntityCollection
	 */
	private function applyFilter(IEntityCollection $dataSource)
	{
		if ($this->hasFilter())
		{
			if ($this->filter)
			{
				$this->getFilters()->setDefaults($this->filter);
			}
			foreach ($this->getFilters()->getComponents(false, 'DataGrid\\BaseFilter') as $filter)
			{
				$dataSource = $filter->applyFilter($dataSource);
			}
		}
		return $dataSource;
	}

	/**
	 * @param string
	 * @return FulltextFilter
	 */
	public function addFulltextSearch($label = 'Vše')
	{
		$this->filters['___fulltext'] = $filter = new FulltextFilter($label);
		return $filter;
	}

	/**
	 * @param array
	 * @return FulltextFilter
	 */
	public function addSelectFilter(array $items)
	{
		$this->filters['___select'] = $filter = new SelectFilter($items);
		return $filter;
	}

	/**
	 * @return BaseColumn
	 */
	public function addSelection()
	{
		return $this->addColumn(new SelectionColumn($this['selection'] = new Selection), 'select');
	}


}
