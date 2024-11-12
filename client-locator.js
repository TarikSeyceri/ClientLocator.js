let visitorId = null;
(async () => {
    const fingerprintjs = await (await (await import('./fingerprint.v4.js')).load()).get();
    visitorId = fingerprintjs.visitorId;
    //console.log('Visitor ID:', visitorId);
})();

async function getClientLocationInfo(){
    const batteryInfo = await window.navigator?.getBattery();
    const ipAddress = (await (await fetch('https://api.ipify.org?format=json'))?.json())?.ip;
    
    return {
        serverDateTime: "",
        dateTime: new Date().toString(),
        timezone: window.Intl?.DateTimeFormat()?.resolvedOptions()?.timeZone,
        timezoneOffset: (new Date()?.getTimezoneOffset() * -1) / 60,
        language: window.navigator?.language || window.navigator?.userLanguage,
        languages: window.navigator?.languages?.toString(),
        continent: "",
        region: "",
        country: "",
        city: "",
        zipCode: "",
        mapUrl: "",
        ipAddress: ipAddress,
        ipAddressRequest: "",

        connectionType: "",
        connectionTypeClient: window.navigator?.connection?.effectiveType,

        screenWidth: window.screen?.width,
        screenHeight: window.screen?.height,
        screenColorDepth: window.screen?.colorDepth,
        screenPixelDepth: window.screen?.pixelDepth,
        screenOrientation: window.screen?.orientation?.type,
        windowInnerWidth: window.innerWidth,
        windowInnerHeight: window.innerHeight,

        userAgent: window.navigator?.userAgent,
        platform: window.navigator?.platform,

        isTouchDevice: 'ontouchstart' in window || window.navigator?.maxTouchPoints > 0,
        batteryInfo: JSON.stringify({ charging: batteryInfo.charging, level: batteryInfo.level ? batteryInfo.level * 100 : null }),
        //mediaDevices: JSON.stringify((await window.navigator?.mediaDevices?.enumerateDevices()).map(device => device.kind)),

        deviceMemory: window.navigator?.deviceMemory,
        deviceCpuThreads: window.navigator?.hardwareConcurrency,

        //cookies: window.document?.cookie,
        //localStorage: JSON.stringify(window.localStorage),

        requestedUrl: window.location?.href,
        referrerUrl: window.document?.referrer,
        visitorId,
    }
}
