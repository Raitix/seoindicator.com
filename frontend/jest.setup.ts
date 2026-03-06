import "@testing-library/jest-dom";

// Mock matchMedia for components that use media queries (e.g., usePrefersReducedMotion)
// Only define if window exists (jsdom environment, not node environment)
if (typeof window !== 'undefined') {
  Object.defineProperty(window, 'matchMedia', {
    writable: true,
    value: jest.fn().mockImplementation((query: string) => ({
      matches: false,
      media: query,
      onchange: null,
      addListener: jest.fn(), // deprecated
      removeListener: jest.fn(), // deprecated
      addEventListener: jest.fn(),
      removeEventListener: jest.fn(),
      dispatchEvent: jest.fn(),
    })),
  });
}

// Polyfill TextEncoder/TextDecoder for MSW 2.x in Jest environment
import { TextEncoder, TextDecoder } from "util";
if (typeof globalThis.TextEncoder === "undefined") {
  globalThis.TextEncoder = TextEncoder as any;
  globalThis.TextDecoder = TextDecoder as any;
}

// Polyfill BroadcastChannel for MSW 2.x
if (typeof globalThis.BroadcastChannel === "undefined") {
  globalThis.BroadcastChannel = class BroadcastChannel {
    name: string;
    onmessage: ((event: any) => void) | null = null;
    onmessageerror: ((event: any) => void) | null = null;
    
    constructor(name: string) {
      this.name = name;
    }
    
    postMessage(_message: any) {}
    close() {}
    addEventListener(_type: string, _listener: any) {}
    removeEventListener(_type: string, _listener: any) {}
    dispatchEvent(_event: any): boolean { return true; }
  } as any;
}

// Polyfill TransformStream for Playwright in Jest
if (typeof globalThis.TransformStream === "undefined") {
  globalThis.TransformStream = class TransformStream {
    readable: any;
    writable: any;
    constructor() {
      this.readable = {};
      this.writable = {};
    }
  } as any;
}

// Polyfill WritableStream for MSW 2.x in Jest environment
if (typeof globalThis.WritableStream === "undefined") {
  globalThis.WritableStream = class WritableStream {
    _writer: any;
    constructor() {
      this._writer = null;
    }
    getWriter() {
      return {
        write: () => Promise.resolve(),
        close: () => Promise.resolve(),
        abort: () => Promise.resolve(),
        closed: Promise.resolve(),
        ready: Promise.resolve(),
        desiredSize: 1,
        releaseLock: () => {},
      };
    }
    abort() {
      return Promise.resolve();
    }
    close() {
      return Promise.resolve();
    }
    locked = false;
  } as any;
}

// Polyfill ReadableStream for MSW 2.x in Jest environment
if (typeof globalThis.ReadableStream === "undefined") {
  globalThis.ReadableStream = class ReadableStream {
    _reader: any;
    constructor() {
      this._reader = null;
    }
    getReader() {
      return {
        read: () => Promise.resolve({ done: true, value: undefined }),
        cancel: () => Promise.resolve(),
        closed: Promise.resolve(),
        releaseLock: () => {},
      };
    }
    cancel() {
      return Promise.resolve();
    }
    locked = false;
    pipeThrough(transform: any) {
      return transform.writable;
    }
    pipeTo(dest: any) {
      return Promise.resolve();
    }
    tee(): [ReadableStream, ReadableStream] {
      return [this, this];
    }
  } as any;
}

// Polyfill Response/Request/Headers for MSW 2.x in Jest environment
// Node.js 18+ has these globals, but Jest's jsdom environment may not expose them
import { Buffer } from "buffer";

if (typeof globalThis.Response === "undefined") {
  // Simple Response polyfill for MSW compatibility
  globalThis.Response = class Response {
    status: number;
    statusText: string;
    headers: Map<string, string>;
    body: any;
    ok: boolean;

    constructor(body: any, init: ResponseInit = {}) {
      this.body = body;
      this.status = init.status ?? 200;
      this.statusText = init.statusText ?? "";
      this.headers = new Map(Object.entries(init.headers ?? {}));
      this.ok = this.status >= 200 && this.status < 300;
    }

    json() {
      return Promise.resolve(JSON.parse(this.body));
    }

    text() {
      return Promise.resolve(String(this.body));
    }
  } as any;
}

if (typeof globalThis.Request === "undefined") {
  globalThis.Request = class Request {
    url: string;
    method: string;
    headers: Map<string, string>;

    constructor(input: string | { url: string }, init: RequestInit = {}) {
      this.url = typeof input === "string" ? input : input.url;
      this.method = init.method ?? "GET";
      this.headers = new Map(Object.entries(init.headers ?? {}));
    }
  } as any;
}

if (typeof globalThis.Headers === "undefined") {
  globalThis.Headers = class Headers {
    private _headers: Map<string, string>;

    constructor(init: Record<string, string> = {}) {
      this._headers = new Map(Object.entries(init));
    }

    get(name: string): string | null {
      return this._headers.get(name.toLowerCase()) ?? null;
    }

    set(name: string, value: string): void {
      this._headers.set(name.toLowerCase(), value);
    }
  } as any;
}
