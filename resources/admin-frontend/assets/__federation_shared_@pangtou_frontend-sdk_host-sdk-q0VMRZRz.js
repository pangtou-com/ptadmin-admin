let t=null;function n(e){return t=e,e}function r(){if(!t)throw new Error("Host SDK has not been defined yet.");return t}export{n as defineHostSdk,r as getHostSdk};
