<?php declare(strict_types = 1);

namespace App\Model;

use PhpParser\Node;


class CodeSanitizer
{
	/** @var \PhpParser\Parser */
	private $parser;

	/** @var \PhpParser\PrettyPrinter\Standard */
	private $printer;


	public function __construct(\PhpParser\Parser $parser, \PhpParser\PrettyPrinter\Standard $printer)
	{
		$this->parser = $parser;
		$this->printer = $printer;
	}


	public function sanitize(string $code): string
	{
		$allNodes = $this->parser->parse($code) ?? [];
		$filteredNodes = $this->filterNodes($allNodes);
		$filteredCode = $this->printer->prettyPrintFile($filteredNodes);

		return $filteredCode;
	}


	/**
	 * @param  Node[] $nodes
	 * @return Node[]
	 */
	private function filterNodes(array $nodes): array
	{
		$nodes = array_filter($nodes, function (Node $node) {
			return $node instanceof Node\Stmt\Namespace_
				|| $node instanceof Node\Stmt\Use_
				|| $node instanceof Node\Stmt\Function_
				|| $node instanceof Node\Stmt\Class_
				|| $node instanceof Node\Stmt\Interface_
				|| $node instanceof Node\Stmt\Trait_;
		});

		foreach ($nodes as $node) {
			if ($node instanceof Node\Stmt\Namespace_) {
				if ($node->stmts) {
					$node->stmts = $this->filterNodes($node->stmts);
				}

			} elseif ($node instanceof Node\Stmt\ClassLike) {
				foreach ($node->stmts as $stmt) {
					if ($stmt instanceof Node\Stmt\ClassMethod && $stmt->stmts) {
						$stmt->stmts = [];
					}
				}

			} elseif ($node instanceof Node\Stmt\Function_) {
				$node->stmts = [];
			}
		}

		$nodes = array_values($nodes); // required for PhpParser\Parser

		return $nodes;
	}
}
