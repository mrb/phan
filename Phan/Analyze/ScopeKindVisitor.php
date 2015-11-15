<?php declare(strict_types=1);
namespace Phan\Analyze;

use \Phan\Language\AST\KindVisitorImplementation;
use \Phan\Language\Context;
use \Phan\Language\FQSEN;
use \Phan\Log;
use \ast\Node;

class ScopeKindVisitor extends KindVisitorImplementation {

    /**
     * @var Context
     * The context in which the node we're going to be looking
     * at exits.
     */
    protected $context;

    /**
     * @param Context $context
     * The context of the parser at the node for which we'd
     * like to determine a type
     */
    public function __construct(Context $context) {
        $this->context = $context;
    }

    /**
     * Default visitor for node kinds that do not have
     * an overriding method
     *
     * @param Node $node
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visit(Node $node) : Context {
        // Many nodes don't change the context and we
        // don't need to read them.
        return $this->context;
    }

    /**
     * Visit a node with kind `\ast\AST_NAMESPACE`
     *
     * @param Node $node
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitNamespace(Node $node) : Context {
        $namespace = '\\' . (string)$node->children['name'];
        return $this->context->withNamespace($namespace);
    }

    /**
     * Visit a node with kind `\ast\AST_IF`
     *
     * @param Node $node
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitIf(Node $node) : Context {
        $context = $this->context->withIsConditional(true);
        return $context;
    }

    /**
     * Visit a node with kind `\ast\AST_GROUP_USE`
     * such as `use \ast\Node;`.
     *
     * @param Node $node
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitGroupUse(Node $node) : Context {

        $prefix = array_shift($node->children);

        $context = $this->context;

        foreach ($this->aliasTargetMapFromUseNode($node->children['uses'], $prefix)
            as $alias => $map
        ) {
            list($flags, $target) = $map;
            $context = $context->withNamespaceMap(
                $flags, $alias, $target
            );
        }

        return $context;
    }

    /**
     * Visit a node with kind `\ast\AST_USE`
     * such as `use \ast\Node;`.
     *
     * @param Node $node
     * A node to parse
     *
     * @return Context
     * A new or an unchanged context resulting from
     * parsing the node
     */
    public function visitUse(Node $node) : Context {
        $context = $this->context;

        foreach ($this->aliasTargetMapFromUseNode($node)
            as $alias => $map
        ) {
            list($flags, $target) = $map;
            print "$alias => $target\n";
            $context = $context->withNamespaceMap(
                $node->flags, $alias, $target
            );
        }

        return $context;
    }

    /**
     * @return array
     * A map from alias to target
     */
    private function aliasTargetMapFromUseNode(
        Node $node,
        string $prefix = ''
    ) : array {
        assert($node->kind == \ast\AST_USE,
            'Method takes AST_USE nodes');

        $map = [];
        foreach($node->children as $child_node) {
            $target = $child_node->children['name'];

            if(empty($child_node->children['alias'])) {
                if(($pos = strrpos($target, '\\'))!==false) {
                    $alias = substr($target, $pos + 1);
                } else {
                    $alias = $target;
                }
            } else {
                $alias = $child_node->children['alias'];
            }

            if ($node->flags == T_FUNCTION) {
                $parts = explode('\\', $target);
                $function_name = array_pop($parts);
                $target = new FQSEN(
                    implode('\\', $parts),
                    '',
                    $function_name
                );
            } else {
                $target = FQSEN::fromFullyQualifiedString(
                    $prefix . '\\' . $target
                );
            }

            $map[$alias] = [$child_node->flags, $target];
        }

        return $map;
    }
}
