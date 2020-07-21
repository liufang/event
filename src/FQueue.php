<?php
namespace Fang;

/**
 * 简单队列实现
 */
class FQueue {
	/**
	 * 缓存
	 */
	private $bufs = [];

	public function push($data)
	{
		array_push($this->bufs, $data);
	}

	public function pop()
	{
		return array_pop($this->bufs);
	}

	public function shift()
	{
		return array_shift($this->bufs);
	}

	public function unshift($data)
	{
		array_unshift($this->bufs, $data);
	}

	public function count()
	{
		return count($this->bufs);
	}
}