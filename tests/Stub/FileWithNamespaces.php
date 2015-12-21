<?php

/**
 * Namespace documentation block
 */
namespace Go\ParserReflection\Stub
{
    const START_MARKER = __LINE__; // Do not move it anywhere

    use ReflectionClass as UnusedReflectionClass;
    use PhpParser\Node as UnusedNode, PhpParser\Node\Expr as UnusedNodeExpr;

    const NAMESPACE_NAME = __NAMESPACE__;
    const FILE_NAME      = __FILE__;

    class TestNamespaceClassFoo {}
    class TestNamespaceClassBar {}
    function testFunctionBar() {}

    $a = testFunctionBar(); // Some top-level code, just for the smoke test

    const END_MARKER = __LINE__; // Do not move it anywhere
}
