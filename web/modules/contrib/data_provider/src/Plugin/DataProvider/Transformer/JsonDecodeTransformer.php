<?php

declare(strict_types=1);

namespace Drupal\data_provider\Plugin\DataProvider\Transformer;

use Psr\Http\Message\ResponseInterface;
use Drupal\data_provider\Contracts\DataProviderTransformerDataInterface;

/**
 * Define the JSON decode transformer plugin.
 *
 * @DataProviderTransformer(
 *   id = "json_decode",
 *   label = @Translation("JSON Decode"),
 *   support_multiple = TRUE,
 * )
 */
class JsonDecodeTransformer extends DataProviderTransformerBase {

  /**
   * {@inheritDoc}
   */
  public function isApplicable(DataProviderTransformerDataInterface $data): bool {
    $value = $data->getValue();
    return $value instanceof ResponseInterface || is_string($value);
  }

  /**
   * {@inheritDoc}
   */
  public function transform(DataProviderTransformerDataInterface $data): array {
    $value = $data->getValue();

    if ($value instanceof ResponseInterface) {
      $value = $value->getBody()->getContents();
    }

    return json_decode($value, TRUE);
  }

}
