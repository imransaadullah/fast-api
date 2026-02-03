# FastAPI Framework Documentation

Welcome to the comprehensive documentation for the FastAPI Framework. This documentation is organized by feature to help you quickly find the information you need.

## 📚 Documentation Index

### 🚀 Core Framework
- **[Getting Started](getting-started.md)** - Quick start guide and basic setup
- **[App Class Guide](app-class.md)** - Application lifecycle and singleton pattern
- **[Router Class Guide](router-class.md)** - Advanced routing with groups and middleware
- **[Fluent API Guide](fluent-api-guide.md)** - Method chaining for route definition with middleware
- **[Request/Response Guide](request-response.md)** - HTTP request and response handling

### 🔧 Middleware System
- **[Complete Middleware Guide](middleware-complete-guide.md)** - Comprehensive middleware documentation
- **[Middleware Examples](middleware-examples.md)** - Real-world middleware implementations
- **[Middleware Best Practices](middleware-best-practices.md)** - Security and performance guidelines

### 🚦 Rate Limiting
- **[Rate Limiting Guide](rate-limiting.md)** - Flexible rate limiting with Redis/DB/File backends, extension requirements, and performance optimization
- **[Rate Limiting Quick Reference](rate-limiting-quick-reference.md)** - Quick setup and troubleshooting guide
- **[Auto-Fallback Rate Limiting](auto-fallback-rate-limiting.md)** - Revolutionary auto-fallback system with Redis → Database → Memory → File fallback chain
- **[Auto-Fallback Quick Reference](auto-fallback-quick-reference.md)** - Quick reference for the auto-fallback system
- **[App Class Rate Limiting](app-class.md#rate-limiting)** - Built-in automatic rate limiting enforcement

### 🌐 WebSocket Support
- **[WebSocket Guide](websocket.md)** - Complete WebSocket implementation guide
- **[WebSocket Quick Reference](websocket-quick-reference.md)** - Quick reference for common patterns
- **[WebSocket Examples](websocket-examples.md)** - Real-time application examples
- **[WebSocket Security](websocket-security.md)** - Authentication and security best practices

### 🔐 Security & Authentication
- **[Token (JWT) Guide](token.md)** - JWT token handling and security
- **[Authentication Examples](authentication-examples.md)** - Complete auth system implementation
- **[Security Best Practices](security-best-practices.md)** - Security guidelines and recommendations

### ⏰ Date & Time Utilities
- **[CustomTime Guide](customtime.md)** - Advanced date/time manipulation
- **[CustomTime Examples](customtime-examples.md)** - Date/time use cases and patterns

### 🛠 Utility Classes
- **[StringMethods Guide](stringmethods.md)** - String manipulation utilities
- **[ArrayMethods Guide](arraymethods.md)** - Array manipulation utilities
- **[Utilities Examples](utilities-examples.md)** - Common utility patterns

### 📋 API Reference
- **[Complete API Reference](api-reference.md)** - All classes and methods
- **[Class Reference](class-reference.md)** - Individual class documentation

### 🏗 Advanced Topics
- **[Route Groups Guide](route-groups.md)** - Advanced routing with groups
- **[Controller Integration](controller-integration.md)** - Laravel-style controller syntax
- **[Error Handling](error-handling.md)** - Exception handling and debugging
- **[Performance Optimization](performance.md)** - Performance tips and best practices
- **[Troubleshooting Guide](troubleshooting.md)** - Common issues and solutions

### 🔄 Migration & Compatibility
- **[Migration Guide](migration-guide.md)** - Upgrading from previous versions
- **[Backward Compatibility](backward-compatibility.md)** - Ensuring compatibility

### 📖 Tutorials & Examples
- **[Healthcare API Tutorial](healthcare-api-tutorial.md)** - Complete healthcare system example
- **[E-commerce API Tutorial](ecommerce-api-tutorial.md)** - E-commerce system implementation
- **[Real-time Chat Tutorial](realtime-chat-tutorial.md)** - WebSocket chat application
- **[REST API Tutorial](rest-api-tutorial.md)** - Building RESTful APIs

### 🧪 Testing
- **[Testing Guide](testing-guide.md)** - Testing strategies and examples
- **[Test Examples](test-examples.md)** - Comprehensive test cases

## 🎯 Quick Navigation

### By Use Case
- **Building APIs**: [Getting Started](getting-started.md) → [Router Class Guide](router-class.md) → [REST API Tutorial](rest-api-tutorial.md)
- **Rate Limiting**: [Rate Limiting Guide](rate-limiting.md) → [App Class Guide](app-class.md) → Extension Setup
- **Real-time Applications**: [WebSocket Guide](websocket.md) → [Real-time Chat Tutorial](realtime-chat-tutorial.md)
- **Authentication Systems**: [Token Guide](token.md) → [Authentication Examples](authentication-examples.md)
- **Healthcare Applications**: [Healthcare API Tutorial](healthcare-api-tutorial.md)
- **E-commerce Systems**: [E-commerce API Tutorial](ecommerce-api-tutorial.md)

### By Experience Level
- **Beginner**: [Getting Started](getting-started.md) → [App Class Guide](app-class.md) → [Request/Response Guide](request-response.md)
- **Intermediate**: [Router Class Guide](router-class.md) → [Middleware Guide](middleware-complete-guide.md) → [Route Groups Guide](route-groups.md)
- **Advanced**: [WebSocket Guide](websocket.md) → [Performance Optimization](performance.md) → [Testing Guide](testing-guide.md)
- **Troubleshooting**: [Troubleshooting Guide](troubleshooting.md) → [Rate Limiting Quick Reference](rate-limiting-quick-reference.md)

## 🔍 Search Documentation

Use the search functionality in your IDE or browser to quickly find specific topics:
- **Middleware**: Search for "middleware" to find all middleware-related documentation
- **WebSocket**: Search for "websocket" for real-time communication guides
- **Authentication**: Search for "auth" or "token" for security documentation
- **Routing**: Search for "route" or "router" for routing guides
- **Extensions**: Search for "redis", "pdo", or "extension" for installation guides
- **Troubleshooting**: Search for "error", "issue", or "problem" for solutions

## 📝 Contributing to Documentation

If you find any issues or want to improve the documentation:
1. Check the [Documentation Style Guide](docs-style-guide.md)
2. Follow the [Contributing Guidelines](../CONTRIBUTING.md)
3. Submit a pull request with your improvements

## 🆘 Getting Help

- **Issues**: [GitHub Issues](https://github.com/imransaadullah/fast-api/issues)
- **Discussions**: [GitHub Discussions](https://github.com/imransaadullah/fast-api/discussions)
- **Examples**: Check the `examples/` directory for working code examples
- **Tests**: Check the `test/` directory for usage examples

---

**Last Updated**: December 2024  
**Version**: 2.2.4  
**Framework Version**: FastAPI v2.2.4
