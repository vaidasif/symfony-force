<?php namespace AdminBundle\Form;

use AppBundle\Entity\MailTemplate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class MailTemplateType
 */
class MailTemplateType extends AbstractType
{
    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return 'template';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('alias', 'text', [
                'label' => 'Alias'
            ])
            ->add('subject', 'text', [
                'label' => 'Subject'
            ])
            ->add('content', 'textarea', [
                'label' => 'Content'
            ])
        ;
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options.
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => MailTemplate::class
        ]);
    }
}
