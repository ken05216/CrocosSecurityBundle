<?php

namespace Crocos\SecurityBundle\Security;

use Doctrine\Common\Annotations\Reader;
use Crocos\SecurityBundle\Annotation\Secure;
use Crocos\SecurityBundle\Security\AuthStrategy\AuthStrategyResolver;

/**
 * AnnotationLoader.
 *
 * @author Katsuhiro Ogawa <ogawa@crocos.co.jp>
 */
class AnnotationLoader
{
    /**
     * @var Reader
     */
    protected $reader;

    /**
     * Constructor.
     *
     * @param Reader $reader Annotation reader
     * @param AuthStrategyResolver $resolver
     */
    public function __construct(Reader $reader, AuthStrategyResolver $resolver)
    {
        $this->reader = $reader;
        $this->resolver = $resolver;
    }

    /**
     * Read security annotation.
     *
     * @param SecurityContext $context
     * @param \ReflectionClass $class
     * @param \ReflectionMethod $method
     */
    public function load(SecurityContext $context, \ReflectionClass $class, \ReflectionMethod $method)
    {
        $klass = $class;
        $classes = array($klass);
        while ($klass = $klass->getParentClass()) {
            $classes[] = $klass;
        }

        $classes = array_reverse($classes);
        foreach ($classes as $class) {
            foreach ($this->reader->getClassAnnotations($class) as $annotation) {
                if ($annotation instanceof Secure) {
                    $this->loadAnnotation($context, $annotation);
                }
            }
        }

        foreach ($this->reader->getMethodAnnotations($method) as $annotation) {
            if ($annotation instanceof Secure) {
                $this->loadAnnotation($context, $annotation);
            }
        }

        $this->resolveAuthStrategy($context);
    }

    /**
     * Load @Secure annotation.
     */
    protected function loadAnnotation(SecurityContext $context, Secure $annotation)
    {
        $context->setSecure(!$annotation->disabled());

        if (null !== $annotation->roles()) {
            $context->setRequiredRoles($annotation->roles());
        }

        if (null !== $annotation->domain()) {
            $context->setDomain($annotation->domain());
        }

        if (null !== $annotation->strategy()) {
            $context->setStrategy($annotation->strategy());
        }

        if (null !== $annotation->forward()) {
            $context->setForwardingController($annotation->forward());
        }
    }

    protected function resolveAuthStrategy($context)
    {
        $strategy = $this->resolver->resolveAuthStrategy($context->getStrategy());

        $strategy->setDomain($context->getDomain());

        $context->setStrategy($strategy);
    }
}