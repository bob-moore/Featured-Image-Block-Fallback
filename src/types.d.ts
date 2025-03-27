/**
 * Block Edit Props Type Definition
 */
export type BlockEditProps<T = {}> = {
    attributes: T;
    setAttributes: (attrs: Partial<T>) => void;
    clientId: string;
    isSelected: boolean;
    context: Record<string, any>;
    name: string;
    insertBlocksAfter?: (blocks: any[]) => void;
    onReplace?: (blocks: any[]) => void;
    mergeBlocks?: (forward: boolean) => void;
    __unstableLayoutClassNames?: string;
    toggleSelection?: (value?: boolean) => void;
};

export type BlockAttributes<T = {}> = {
	className: string;
	clientId: string;
	name: string;
    featuredImageFallback: Image | null;
} & T;

export interface Image {
    id: number;
    url: string;
}