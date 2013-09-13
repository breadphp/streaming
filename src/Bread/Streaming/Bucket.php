<?php
namespace Bread\Streaming;

use Bread\Streaming\Interfaces\Readable;
use Bread\Streaming\Interfaces\Writable;
use Bread\Event\Emitter;

class Bucket extends Emitter implements Writable
{

    protected $source;
    protected $stream;
    protected $closed = false;
    protected $closing = false;
    protected $writable = true;
    
    public function __construct(Readable $source)
    {
        $this->source = $source;
        $this->source->pipe($this);
        $this->stream = fopen('php://temp', 'r+');
        $this->source->on('end', array($this, 'end'));
    }

    public function rewind()
    {
        rewind($this->stream);
    }
    
    public function contents()
    {
        $this->rewind();
        return stream_get_contents($this->stream);
    }
    
    public function write($data)
    {
        if (!$this->writable) {
            return;
        }
        fwrite($this->stream, $data);
    }

    public function end($data = null)
    {
        $this->closing = true;
        $this->writable = false;
        $this->emit('end', array(
            $data
        ));
    }
    
    public function close()
    {
        if ($this->closed) {
            return;
        }
        $this->closed = true;
        $this->emit('end', array(
            $this
        ));
        $this->emit('close', array(
            $this
        ));
        $this->removeAllListeners();
    }
    
    public function isWritable() {
        return $this->writable;
    }

}
