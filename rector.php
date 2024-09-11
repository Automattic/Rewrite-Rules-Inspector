<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php54\Rector\Array_\LongArrayToShortArrayRector;
use Rector\Php82\Rector\Encapsed\VariableInStringInterpolationFixerRector;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\Php84\Rector\Param\ExplicitNullableParamTypeRector;

return RectorConfig::configure()
	->withPaths(
		array(
			__DIR__ . '/src',
			__DIR__ . '/rewrite-rules-inspector.php',
		)
	)
	->withSkip(
		array(
			LongArrayToShortArrayRector::class,
		)
	)
	->withPhpSets( php74: true )
	// Changes from later PHP Sets that are backwards compatible:
	->withRules(
		array(
			// 8.2
			VariableInStringInterpolationFixerRector::class,

			// 8.3
			AddOverrideAttributeToOverriddenMethodsRector::class,

			// 8.4
			ExplicitNullableParamTypeRector::class,
		)
	)
//	->withPreparedSets( deadCode: true, codeQuality: true, instanceOf: true, codingStyle: true )
//	->withTypeCoverageLevel( 1 )
	;
