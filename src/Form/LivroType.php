<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Assunto;
use App\Entity\Autor;
use App\Entity\Livro;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @extends AbstractType<Livro>
 */
final class LivroType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $livro = $options['data'] instanceof Livro ? $options['data'] : null;
        $autores = $livro?->getAutores()->map(static fn (Autor $autor): string => $autor->getNome())->toArray() ?? [];
        $assuntos = $livro?->getAssuntos()->map(static fn (Assunto $assunto): string => $assunto->getDescricao())->toArray() ?? [];

        $builder
            ->add('titulo', TextType::class, [
                'label' => 'Título',
                'attr' => ['maxlength' => 40, 'autocomplete' => 'off'],
                'constraints' => [new NotBlank(), new Length(max: 40)],
            ])
            ->add('editora', TextType::class, [
                'label' => 'Editora',
                'attr' => ['maxlength' => 40, 'autocomplete' => 'organization'],
                'constraints' => [new NotBlank(), new Length(max: 40)],
            ])
            ->add('edicao', IntegerType::class, [
                'label' => 'Edição',
                'attr' => ['min' => 1],
                'constraints' => [new NotBlank(), new Positive()],
            ])
            ->add('anoPublicacao', TextType::class, [
                'label' => 'Ano de publicação',
                'attr' => ['maxlength' => 4, 'inputmode' => 'numeric', 'placeholder' => '2026'],
                'constraints' => [
                    new NotBlank(),
                    new Regex(pattern: '/^\d{4}$/', message: 'Informe o ano com quatro algarismos.'),
                ],
            ])
            ->add('valor', TextType::class, [
                'label' => 'Valor (R$)',
                'attr' => [
                    'data-currency-input' => 'true',
                    'inputmode' => 'numeric',
                    'maxlength' => 12,
                    'pattern' => '(?:\\d{1,8}|\\d{1,2}(?:\\.\\d{3}){1,2}|\\d{1,3}\\.\\d{3}),\\d{2}',
                    'placeholder' => '0,00',
                    'autocomplete' => 'off',
                ],
                'constraints' => [
                    new NotBlank(),
                    new Regex(
                        pattern: '/^\d{1,8}(?:\.\d{1,2})?$/',
                        message: 'Informe um valor válido no formato brasileiro, com até duas casas decimais.'
                    ),
                ],
            ])
            ->add('autoresTexto', TextType::class, [
                'mapped' => false,
                'data' => implode('; ', $autores),
                'label' => '',
                'required' => false,
                'attr' => [
                    'data-tag-input' => 'true',
                    'maxlength' => 1000,
                    'autocomplete' => 'off',
                    'aria-label' => 'Autores',
                    'placeholder' => 'Digite um autor e pressione ; ou Enter',
                ],
                'constraints' => [
                    new Length(max: 1000),
                    $this->tagLengthConstraint(40, 'Cada nome de autor'),
                ],
            ])
            ->add('assuntosTexto', TextType::class, [
                'mapped' => false,
                'data' => implode('; ', $assuntos),
                'label' => '',
                'required' => false,
                'attr' => [
                    'data-tag-input' => 'true',
                    'maxlength' => 1000,
                    'autocomplete' => 'off',
                    'aria-label' => 'Assuntos',
                    'placeholder' => 'Digite um assunto e pressione ; ou Enter',
                ],
                'constraints' => [
                    new Length(max: 1000),
                    $this->tagLengthConstraint(20, 'Cada assunto'),
                ],
            ]);

        $builder->get('valor')->addEventListener(FormEvents::PRE_SUBMIT, static function (FormEvent $event): void {
            $value = $event->getData();

            if (is_string($value)) {
                $value = trim($value);

                if (preg_match('/^(?:\d{1,8}|\d{1,2}(?:\.\d{3}){1,2}|\d{1,3}\.\d{3}),\d{2}$/', $value) === 1) {
                    $value = str_replace(',', '.', str_replace('.', '', $value));
                }

                $event->setData($value);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Livro::class]);
    }

    private function tagLengthConstraint(int $maxLength, string $label): Callback
    {
        return new Callback(static function (mixed $value, ExecutionContextInterface $context) use ($maxLength, $label): void {
            if (!is_string($value)) {
                return;
            }

            foreach (explode(';', $value) as $tag) {
                $tag = trim($tag);

                if ($tag !== '' && preg_match('/^.{1,'.$maxLength.'}$/u', $tag) !== 1) {
                    $context->buildViolation($label.' deve ter no máximo '.$maxLength.' caracteres.')
                        ->addViolation();
                }
            }
        });
    }
}
