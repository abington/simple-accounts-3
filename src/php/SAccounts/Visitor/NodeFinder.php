<?php
/**
 * Simple Double Entry Accounting V2
 
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2018, UK
 * @license GPL V3+ See LICENSE.md
 */
namespace SAccounts\Visitor;

use Tree\Visitor\Visitor;
use Tree\Node\NodeInterface;
use Tree\Node\Node;

/**
 * Find an account node in the chart tree
 */
class NodeFinder implements Visitor
{
    /**
     * @var Nominal
     */
    protected $valueToFind;

    /**
     * @param Nominal $valueToFind Node value to find
     */
    public function __construct(Nominal $valueToFind)
    {
        $this->valueToFind = $valueToFind;
    }

    /**
     * @param NodeInterface $node
     * @return Node|null
     */
    public function visit(NodeInterface $node)
    {
        $currAc = $node->getValue();

        if ($currAc instanceof Account && $currAc->getNominal() == $this->valueToFind) {
            return $node;
        }

        foreach ($node->getChildren() as $child) {
            /** @noinspection PhpVoidFunctionResultUsedInspection */
            $found = $child->accept($this);
            if (!is_null($found)) {
                return $found;
            }
        }

        return null;
    }
}