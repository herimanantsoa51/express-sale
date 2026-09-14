class HelloWorldTest extends PHPUnit\Framework\TestCase {
    public function testHelloWorld() {
        $this->assertEquals('Hello, World!', 'Hello, World!');
    }
}