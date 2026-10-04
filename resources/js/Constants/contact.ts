export const WHATSAPP_NUMBER = "972587260264";

export const whatsappUrl = (message: string): string =>
    `https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent(message)}`;
