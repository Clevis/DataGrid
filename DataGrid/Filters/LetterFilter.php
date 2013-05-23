<?php
namespace DataGrid;

use Nette\Utils\Strings;
use Orm\IEntityCollection;
use Nette\NotSupportedException;


/**
 *
 */
class LetterFilter extends TextFilter
{
	static private $letters;

	private $usedChars;

	public function __construct(array $usedChars = NULL)
	{
		$this->usedChars = $usedChars;
		if (!isset(self::$letters))
		{
			self::$letters = array();
			foreach (range('a', 'z')/*, range(0, 9), */ as $char)
			{
				self::$letters[$char] = array($char => $char);
			}
			self::$letters['?'] = array();
		}
		$this->monitor('Grid');
		parent::__construct();
	}

	protected function create()
	{
		$this->addSelect('text', NULL)
			->setItems(array_keys(self::$letters), false)
			->setPrompt('');;
	}

	protected function attached($grid)
	{
		parent::attached($grid);
		if ($grid instanceof Grid)
		{
			if ($this->usedChars === NULL)
			{
				$ds = clone $grid->getDataSource();
				if (!($ds instanceof \DibiDataSource))
					throw new NotSupportedException();

				$usedChars = $ds->select($this->name)->fetchPairs($this->name, $this->name);
			}
			else
			{
				$usedChars = $this->usedChars;
			}
			foreach ($usedChars as $value)
			{
				$char = $key = '';
				if ($value)
				{
					if ($this->usedChars === NULL)
					{
						$char = iconv_substr($value, 0, 1, 'UTF-8');
					}
					else
					{
						$char = $value;
					}
					$key = strtolower(Strings::toAscii($char));
				}
				self::$letters[isset(self::$letters[$key]) ? $key : '?'][$char] = $char;
			}
		}
	}

	protected function defaultFilter(IEntityCollection $dataSource, $value)
	{
		if ($dataSource instanceof \DibiDataSource)
		{
			return $dataSource->where('IFNULL(SUBSTRING(%n, 1, 1), "") IN %in', $this->name, self::$letters[$value])->toDataSource();
		}
		else
		{
			throw new NotSupportedException();
		}
	}
}
