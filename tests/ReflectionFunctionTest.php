<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types=1);

namespace Go\ParserReflection;

use Closure;
use PHPUnit\Framework\TestCase;
use ReflectionFunction as BaseReflectionFunction;
use ReflectionUnionType as BaseReflectionUnionType;

class ReflectionFunctionTest extends TestCase
{
    const STUB_FILE55 = '/Stub/FileWithFunctions55.php';
    const STUB_FILE70 = '/Stub/FileWithFunctions70.php';
    const STUB_FILE80 = '/Stub/FileWithFunctions80.php';

    /**
     * @var ReflectionFile
     */
    protected ReflectionFile $parsedRefFile;

    protected function setUp(): void
    {
        $fileName = stream_resolve_include_path(__DIR__ . self::STUB_FILE55);

        $reflectionFile = new ReflectionFile($fileName);
        $this->parsedRefFile = $reflectionFile;

        include_once $fileName;
    }

    public function testGeneralInfoGetters()
    {
        $allNameGetters = [
            'getStartLine', 'getEndLine', 'getDocComment', 'getExtension', 'getExtensionName',
            'getName', 'getNamespaceName', 'getShortName', 'inNamespace', 'getStaticVariables',
            'getNumberOfParameters', 'getNumberOfRequiredParameters', '__toString', 'isDisabled',
            'returnsReference', 'getClosureScopeClass', 'getClosureThis'
        ];

        if (PHP_VERSION_ID >= 70000) {
            $allNameGetters[] = 'hasReturnType';
        }

        foreach ($this->parsedRefFile->getFileNamespaces() as $fileNamespace) {
            foreach ($fileNamespace->getFunctions() as $refFunction) {
                $functionName        = $refFunction->getName();
                $originalRefFunction = new BaseReflectionFunction($functionName);
                foreach ($allNameGetters as $getterName) {
                    $expectedValue = $originalRefFunction->$getterName();
                    $actualValue   = $refFunction->$getterName();
                    $this->assertSame(
                        $expectedValue,
                        $actualValue,
                        "$getterName() for function $functionName should be equal"
                    );
                }
            }
        }
    }

    public function testCoverAllMethods()
    {
        $allInternalMethods = get_class_methods(BaseReflectionFunction::class);
        $allMissedMethods   = [];

        foreach ($allInternalMethods as $internalMethodName) {
            if ('export' === $internalMethodName) {
                continue;
            }
            $refMethod    = new \ReflectionMethod(ReflectionFunction::class, $internalMethodName);
            $definerClass = $refMethod->getDeclaringClass()->getName();
            if (!str_starts_with($definerClass, 'Go\\ParserReflection')) {
                $allMissedMethods[] = $internalMethodName;
            }
        }

        if ($allMissedMethods) {
            $this->markTestIncomplete('Methods ' . join(', ', $allMissedMethods) . ' are not implemented');
        } else {
            $this->assertTrue(true);
        }
    }

    public function testGetClosureMethod()
    {
        $fileNamespace = $this->parsedRefFile->getFileNamespace('Go\ParserReflection\Stub');
        $refFunc       = $fileNamespace->getFunction('noGeneratorFunc');
        $closure       = $refFunc->getClosure();

        $this->assertInstanceOf(Closure::class, $closure);
        $retValue = $closure();
        $this->assertEquals(100, $retValue);
    }

    public function testInvokeMethod()
    {
        $fileNamespace = $this->parsedRefFile->getFileNamespace('Go\ParserReflection\Stub');
        $refFunc       = $fileNamespace->getFunction('funcWithReturnArgs');
        $retValue      = $refFunc->invoke(1, 2, 3);
        $this->assertEquals([1, 2, 3], $retValue);
    }

    public function testInvokeArgsMethod()
    {
        $fileNamespace = $this->parsedRefFile->getFileNamespace('Go\ParserReflection\Stub');
        $refFunc       = $fileNamespace->getFunction('funcWithReturnArgs');
        $retValue      = $refFunc->invokeArgs([1, 2, 3]);
        $this->assertEquals([1, 2, 3], $retValue);
    }

    public function testGetReturnTypeMethod()
    {
        if (PHP_VERSION_ID < 70000) {
            $this->markTestSkipped('Test available only for PHP7.0 and newer');
        }

        $fileName = stream_resolve_include_path(__DIR__ . self::STUB_FILE70);

        $reflectionFile = new ReflectionFile($fileName);
        include $fileName;

        foreach ($reflectionFile->getFileNamespaces() as $fileNamespace) {
            foreach ($fileNamespace->getFunctions() as $refFunction) {
                $functionName        = $refFunction->getName();
                $originalRefFunction = new BaseReflectionFunction($functionName);
                $hasReturnType       = $refFunction->hasReturnType();
                $this->assertSame(
                    $originalRefFunction->hasReturnType(),
                    $hasReturnType,
                    "Presence of return type for function $functionName should be equal"
                );
                if ($hasReturnType) {
                    $parsedReturnType   = $refFunction->getReturnType();
                    $originalReturnType = $originalRefFunction->getReturnType();
                    $this->assertSame($originalReturnType->allowsNull(), $parsedReturnType->allowsNull());
                    $this->assertSame($originalReturnType->isBuiltin(), $parsedReturnType->isBuiltin());
                    // TODO: To prevent deprecation error in tests
                    if (PHP_VERSION_ID < 70400) {
                        $this->assertSame($originalReturnType->__toString(), $parsedReturnType->__toString());
                    } else {
                        $this->assertSame($originalReturnType->getName(), $parsedReturnType->__toString());
                    }
                } else {
                    $this->assertSame(
                        $originalRefFunction->getReturnType(),
                        $refFunction->getReturnType()
                    );
                }
            }
        }
    }

    public function testGetUnionParameters()
    {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped('Test available only for PHP8.0 and newer');
        }

        $fileName = stream_resolve_include_path(__DIR__ . self::STUB_FILE80);

        $reflectionFile = new ReflectionFile($fileName);
        include $fileName;

        foreach ($reflectionFile->getFileNamespaces() as $fileNamespace) {
            foreach ($fileNamespace->getFunctions() as $refFunction) {
                $functionName        = $refFunction->getName();
                $originalRefFunction = new BaseReflectionFunction($functionName);
                $this->assertSame(
                    $originalRefFunction->getNumberOfParameters(),
                    $refFunction->getNumberOfParameters(),
                    "Number of parameters for function $functionName should be equal"
                );
                $this->assertSame(
                    $originalRefFunction->getNumberOfRequiredParameters(),
                    $refFunction->getNumberOfRequiredParameters(),
                    "Number of required parameters for function $functionName should be equal"
                );
                foreach ($refFunction->getParameters() as $parameter) {
                    $originalParameter = $originalRefFunction->getParameters()[$parameter->getPosition()];
                    $this->assertSame(
                        $originalParameter->getType() instanceof BaseReflectionUnionType,
                        $parameter->getType() instanceof BaseReflectionUnionType,
                        "Presence of union type for parameter {$parameter->getName()} of function $functionName should be equal"
                    );
                }
            }
        }
    }
}
