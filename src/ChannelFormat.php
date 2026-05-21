<?php

declare(strict_types=1);

namespace PhpMlKit\Opal;

/**
 * Memory layout for multichannel image tensors when importing/exporting NDArray.
 */
enum ChannelFormat
{
    /** [height, width, channels] — TensorFlow / NumPy / PIL default. */
    case HWC;

    /** [channels, height, width] — PyTorch / ONNX vision default. */
    case CHW;
}
